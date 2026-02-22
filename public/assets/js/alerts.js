/**
 * alerts.js — Global SweetAlert2 helpers for CreaCo
 * Theme: Refined Soft UI to match Event/Forum screenshots (Pic 2 style)
 */

const SWAL_THEME = {
    customClass: {
        popup: 'swal-creaco-popup',
        confirmButton: 'swal-creaco-confirm',
        cancelButton: 'swal-creaco-cancel',
        title: 'swal-creaco-title',
        htmlContainer: 'swal-creaco-text',
        icon: 'swal-creaco-icon'
    },
    buttonsStyling: false,
    showClass: {
        popup: 'animate__animated animate__fadeIn animate__faster'
    },
    hideClass: {
        popup: 'animate__animated animate__fadeOut animate__faster'
    }
};

// ─── Inject custom CSS ──────────────────────────────────────────────────────
(function injectStyles() {
    if (document.getElementById('swal-creaco-styles')) return;
    const style = document.createElement('style');
    style.id = 'swal-creaco-styles';
    style.textContent = `
        .swal-creaco-popup {
            border-radius: 1rem !important;
            padding: 1.5rem !important;
            background: #ffffff !important;
            box-shadow: 0 20px 27px 0 rgba(0, 0, 0, 0.05) !important;
            width: 32em !important;
        }
        
        .swal-creaco-icon {
            margin-top: 1rem !important;
            border-width: 2px !important;
        }

        .swal-creaco-title {
            color: #344767 !important;
            font-weight: 700 !important;
            font-size: 1.75rem !important;
            margin: 1rem 0 0.5rem !important;
        }

        .swal-creaco-text {
            color: #67748e !important;
            font-weight: 400 !important;
            font-size: 1.1rem !important;
            padding: 0 1rem !important;
        }

        /* Pic 2 Style Buttons */
        .swal-creaco-confirm {
            background-color: #cb0c9f !important;
            color: #fff !important;
            border-radius: 0.5rem !important;
            padding: 0.625rem 1.5rem !important;
            font-weight: 700 !important;
            font-size: 0.875rem !important;
            border: none !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            margin: 0.5rem !important;
        }
        .swal-creaco-confirm:hover {
            opacity: 0.85;
            transform: translateY(-1px);
        }

        .swal-creaco-cancel {
            background-color: #8392ab !important;
            color: #fff !important;
            border-radius: 0.5rem !important;
            padding: 0.625rem 1.5rem !important;
            font-weight: 700 !important;
            font-size: 0.875rem !important;
            border: none !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            margin: 0.5rem !important;
        }
        .swal-creaco-cancel:hover {
            opacity: 0.85;
            transform: translateY(-1px);
        }
    `;
    document.head.appendChild(style);
})();

/**
 * Confirm deletion of an item
 */
window.confirmDelete = function (form, itemName) {
    Swal.fire({
        ...SWAL_THEME,
        icon: 'warning',
        title: 'Are you sure?',
        text: "You are about to delete '" + itemName + "'. This action cannot be undone!",
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
};

/**
 * Generic confirmation for any action
 */
window.confirmAction = function (title, text, icon, confirmBtnText = 'Confirm') {
    return Swal.fire({
        ...SWAL_THEME,
        icon: icon || 'question',
        title: title || 'Are you sure?',
        text: text || '',
        showCancelButton: true,
        confirmButtonText: confirmBtnText,
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        return result.isConfirmed;
    });
};

/**
 * Show a success toast
 */
window.showSuccess = function (message) {
    Swal.fire({
        ...SWAL_THEME,
        icon: 'success',
        title: 'Success!',
        text: message,
        timer: 2000,
        showConfirmButton: false
    });
};

/**
 * Show a "Copied" modal in the center
 */
window.showCopied = function () {
    Swal.fire({
        ...SWAL_THEME,
        icon: 'success',
        title: 'Copied!',
        text: 'Link copied to clipboard.',
        timer: 1500,
        showConfirmButton: false,
        toast: false,
        position: 'center'
    });
};
