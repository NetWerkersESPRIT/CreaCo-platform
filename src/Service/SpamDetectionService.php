<?php

namespace App\Service;

class SpamDetectionService
{
    /**
     * Calculates a spam score for a post or comment based on rule scoring.
     * 
     * @param string $title
     * @param string $content
     * @return int (0-100)
     */
    public function calculateScore(string $title, string $content): int
    {
        $text = $title . ' ' . $content;
        $score = 0;

        // Edge case: empty or very short content
        if (mb_strlen(trim($text)) < 20) {
            return rand(0, 5); // Return a low random score as requested
        }

        // 1. Link counting (+30 if more than 3 links)
        // Matches http, https, and www
        $linkPattern = '/(https?:\/\/[^\s]+)|(www\.[^\s]+)/i';
        preg_match_all($linkPattern, $text, $matches);
        if (count($matches[0]) > 3) {
            $score += 30;
        }

        // 2. Repeated words or repeated sentences (+20)
        // Sentence repetition
        $sentences = preg_split('/[.!?]/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $uniqueSentences = array_unique(array_map('trim', $sentences));
        $repetitionRatio = count($sentences) > 0 ? (count($sentences) - count($uniqueSentences)) / count($sentences) : 0;

        // Word repetition (normalized for longer texts)
        $words = str_word_count(strtolower($text), 1);
        $uniqueWords = array_unique($words);
        $wordRepetitionRatio = count($words) > 0 ? (count($words) - count($uniqueWords)) / count($words) : 0;

        if ($repetitionRatio > 0.3 || $wordRepetitionRatio > 0.5) {
            $score += 20;
        }

        // 3. Uppercase ratio (+15 if more than 60%)
        $lettersOnly = preg_replace('/[^a-zA-Z]/', '', $text);
        if (strlen($lettersOnly) > 10) {
            $uppercaseCount = strlen(preg_replace('/[^A-Z]/', '', $lettersOnly));
            if (($uppercaseCount / strlen($lettersOnly)) > 0.6) {
                $score += 15;
            }
        }

        // 4. Excessive emojis or special characters (+15)
        $totalChars = mb_strlen($text);
        if ($totalChars > 0) {
            // Count characters that are NOT letters, digits, or standard punctuation
            $symbolsCount = mb_strlen(preg_replace('/[a-zA-Z0-9\s\.,!\?\(\)\-\'\"À-ÿ]/u', '', $text));
            $symbolDensity = $symbolsCount / $totalChars;
            
            // Check for sequences like !!! or $$$
            preg_match_all('/[!$#%&*?]{3,}/', $text, $seqMatches);

            if ($symbolDensity > 0.15 || count($seqMatches[0]) > 2) {
                $score += 15;
            }
        }

        // 5. Suspicious keywords (+20)
        $suspiciousKeywords = [
            'free', 'promo', 'buy now', 'click here', 'limited offer', 
            'win', 'urgent', 'discount', 'subscribe', 'porn', 'casino',
            'viagra', 'bitcoin', 'crypto', 'investment', 'money', 'cash'
        ];
        
        $foundKeyword = false;
        foreach ($suspiciousKeywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                $foundKeyword = true;
                break;
            }
        }
        
        if ($foundKeyword) {
            $score += 20;
        }

        // Final cap at 100
        return min(100, $score);
    }
}
