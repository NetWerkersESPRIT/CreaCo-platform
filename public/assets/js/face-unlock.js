/**
 * CreaCo Immersive Biometric System
 * Advanced Facial Recognition with Orientation Guidance
 */

// Global Config
const MODEL_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights';
const FACE_THRESHOLD = 0.55;

class FaceIDSystem {
    constructor() {
        this.container = document.getElementById('face-id-immersive');
        if (!this.container) return; // Modal not on this page
        
        this.video = document.getElementById('webcam-immersive');
        this.statusText = document.getElementById('guidance-instruction');
        this.titleText = document.getElementById('guidance-title');
        this.loader = document.getElementById('face-loader-immersive');
        this.successAnim = document.getElementById('success-anim-immersive');
        
        this.isModelsLoaded = false;
        this.isScanning = false;
        this.isSignupMode = false;
        this.stream = null;
        this.currentStep = 0;
        this.stepsCompleted = [false, false, false, false, false];
        
        this.init();
    }

    async init() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('signup_success')) {
            setTimeout(() => this.startRegistration(), 1000);
        }

        const signupBtn = document.getElementById('btn-face-signup');
        if (signupBtn) signupBtn.addEventListener('click', () => {
            this.isSignupMode = true;
            this.startRegistration();
        });

        const loginBtn = document.getElementById('btn-face-login');
        if (loginBtn) loginBtn.addEventListener('click', () => {
            this.isSignupMode = false;
            this.startLogin();
        });

        const registerBtn = document.getElementById('btn-face-register');
        if (registerBtn) registerBtn.addEventListener('click', () => {
            this.isSignupMode = false;
            this.startRegistration(false); // Pass false to run full verification steps
        });

        const cancelBtn = document.getElementById('btn-cancel-immersive');
        if (cancelBtn) cancelBtn.addEventListener('click', () => this.hide());
    }

    async loadModels() {
        if (this.isModelsLoaded) return true;
        this.updateStatus("SYNCING AI CORE...");
        try {
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
            ]);
            this.isModelsLoaded = true;
            return true;
        } catch (e) {
            this.updateStatus("SYNC FAILED", true);
            return false;
        }
    }

    async startCamera() {
        try {
            this.stream = await navigator.mediaDevices.getUserMedia({ 
                video: { width: 1280, height: 720, facingMode: 'user' } 
            });
            this.video.srcObject = this.stream;
            return new Promise(resolve => this.video.onloadedmetadata = () => resolve(true));
        } catch (e) {
            this.updateStatus("CAMERA ERROR", true);
            return false;
        }
    }

    updateStatus(msg, isError = false) {
        if (this.statusText) {
            this.statusText.innerText = msg;
            this.statusText.classList.toggle('text-pink-500', isError);
        }
    }

    updateTitle(msg) {
        if (this.titleText) this.titleText.innerText = msg;
    }

    show() {
        this.container.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    hide() {
        this.container.classList.remove('active');
        document.body.style.overflow = '';
        this.stop();
    }

    stop() {
        if (this.stream) {
            this.stream.getTracks().forEach(t => t.stop());
            this.stream = null;
        }
        this.isScanning = false;
        this.successAnim.classList.remove('show');
    }

    // --- REGISTRATION FLOW ---

    async startRegistration(skipVerification = false) {
        this.show();
        this.updateTitle("VERIFICATION REQUIRED");
        this.updateStatus("INITIALIZING...");
        
        if (!await this.loadModels()) return;
        if (!await this.startCamera()) return;

        this.loader.classList.add('hidden');
        this.isScanning = true;
        this.currentStep = 0;
        
        if (skipVerification) {
            // Logged in user: just capture directly
            this.updateStatus("POSITIONING...");
            this.runDirectCapture();
        } else {
            this.runRegistrationLoop();
        }
    }

    async runDirectCapture() {
        // Hide step dots if skipping verification
        const dots = document.querySelector('.step-dots');
        if (dots) dots.style.display = 'none';

        while (this.isScanning) {
            const detection = await faceapi.detectSingleFace(this.video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (detection) {
                this.updateStatus("CAPTURING DESCRIPTOR...");
                this.saveRegistration(Array.from(detection.descriptor));
                break;
            }
            await new Promise(r => setTimeout(r, 500));
        }
        
        // Reset dots visibility for next time
        if (dots) dots.style.display = '';
    }

    async runRegistrationLoop() {
        const steps = [
            { label: "CENTER YOUR FACE", check: (pose) => pose.isCentered },
            { label: "LOOK SLIGHTLY LEFT", check: (pose) => pose.isLeft },
            { label: "LOOK SLIGHTLY RIGHT", check: (pose) => pose.isRight },
            { label: "LOOK UP", check: (pose) => pose.isUp },
            { label: "LOOK DOWN", check: (pose) => pose.isDown }
        ];

        while (this.isScanning && this.currentStep < steps.length) {
            this.updateStepDots();

            const detection = await faceapi.detectSingleFace(this.video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks();

            if (detection) {
                const pose = this.estimatePose(detection.landmarks);
                if (steps[this.currentStep].check(pose)) {
                    this.stepsCompleted[this.currentStep] = true;
                    this.currentStep++;
                    this.updateStatus("CONFIRMED", false);
                    await new Promise(r => setTimeout(r, 600));
                } else {
                    this.updateStatus(steps[this.currentStep].label);
                }
            } else {
                this.updateStatus("POSING... (Face Not Found)", true);
            }
            await new Promise(r => setTimeout(r, 100));
        }

        if (this.currentStep >= steps.length) {
            this.updateStatus("CAPTURING SECURE DESCRIPTOR...");
            const finalDetection = await faceapi.detectSingleFace(this.video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();
            
            if (finalDetection) {
                this.saveRegistration(Array.from(finalDetection.descriptor));
            }
        }
    }

    // --- LOGIN FLOW ---

    async startLogin() {
        const emailInput = document.querySelector('input[name="email"]');
        const emailWarning = document.getElementById('email-warning');
        
        if (emailInput && !emailInput.value.trim()) {
            // Visual feedback for empty email
            emailWarning.classList.remove('hidden');
            emailInput.classList.add('shake', 'input-error-glow');
            emailInput.focus();
            
            setTimeout(() => {
                emailInput.classList.remove('shake');
            }, 500);
            return;
        }

        if (emailWarning) emailWarning.classList.add('hidden');
        if (emailInput) emailInput.classList.remove('input-error-glow');

        this.show();
        this.updateTitle("VERIFICATION REQUIRED");
        this.updateStatus("SYNCING...");
        
        if (!await this.loadModels()) return;
        if (!await this.startCamera()) return;

        this.loader.classList.add('hidden');
        this.isScanning = true;
        this.runLoginLoop(emailInput ? emailInput.value : null);
    }

    async runLoginLoop(email = null) {
        while (this.isScanning) {
            const detection = await faceapi.detectSingleFace(this.video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (detection) {
                this.updateStatus("FACE DETECTED. VERIFYING...");
                this.verifyLogin(Array.from(detection.descriptor), email);
                break; 
            }
            await new Promise(r => setTimeout(r, 500));
        }
    }

    // --- UTILS ---

    estimatePose(landmarks) {
        const nose = landmarks.getNose()[3]; // Tip of nose
        const leftEye = landmarks.getLeftEye()[0];
        const rightEye = landmarks.getRightEye()[3];
        const topNose = landmarks.getNose()[0];
        const bottomNose = landmarks.getNose()[6];

        const noseToLeft = nose.x - leftEye.x;
        const noseToRight = rightEye.x - nose.x;
        const horizontalRatio = noseToLeft / noseToRight;

        const noseToTop = nose.y - topNose.y;
        const noseToBottom = bottomNose.y - nose.y;
        const verticalRatio = noseToTop / noseToBottom;

        // Loosened thresholds for better user experience
        return {
            isCentered: horizontalRatio > 0.6 && horizontalRatio < 1.6 && verticalRatio > 0.4 && verticalRatio < 1.8,
            isLeft: horizontalRatio < 0.5,
            isRight: horizontalRatio > 2.0,
            isUp: verticalRatio < 0.4,
            isDown: verticalRatio > 1.8
        };
    }

    updateStepDots() {
        const dots = document.querySelectorAll('.step-dot');
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === this.currentStep);
            dot.classList.toggle('completed', this.stepsCompleted[i]);
        });
    }

    async saveRegistration(descriptor) {
        if (this.isSignupMode) {
            // In signup mode, just store descriptor locally in the form
            const input = document.getElementById('face_descriptor_signup');
            if (input) input.value = JSON.stringify(descriptor);
            
            // Update UI feedback on the signup form
            const btnText = document.getElementById('face-signup-text');
            const btnCheck = document.getElementById('face-signup-check');
            if (btnText) btnText.innerText = "Face ID Secured";
            if (btnCheck) btnCheck.classList.remove('hidden');
            
            this.success();
            return;
        }

        try {
            const res = await fetch('/face-id/register', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ descriptor })
            });
            const data = await res.json();
            if (data.success) {
                this.success();
            } else {
                this.updateStatus(data.message, true);
            }
        } catch (e) { this.updateStatus("SERVER ERROR", true); }
    }

    async verifyLogin(descriptor, email = null) {
        try {
            const res = await fetch('/face-id/login', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ descriptor, email })
            });
            const data = await res.json();
            if (data.success) {
                this.success(data.redirect);
            } else {
                this.updateStatus(data.message, true);
                setTimeout(() => {
                    if (this.isScanning) this.runLoginLoop(email);
                }, 3000);
            }
        } catch (e) { this.updateStatus("SERVER ERROR", true); }
    }

    success(redirect = null) {
        this.updateStatus("VERIFIED");
        this.successAnim.classList.add('show');
        setTimeout(() => {
            if (redirect) window.location.href = redirect;
            else this.hide();
        }, 1500);
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    window.faceId = new FaceIDSystem();
});
