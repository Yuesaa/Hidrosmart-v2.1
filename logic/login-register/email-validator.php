<?php
// email-validator.php - Advanced Email Validation Class
class EmailValidator
{

    private $api_keys = [
        // Untuk production, Anda bisa menggunakan API key dari layanan seperti:
        // 'hunter_io' => 'your_hunter_io_api_key',
        // 'zerobounce' => 'your_zerobounce_api_key',
    ];

    /**
     * Main email validation function
     */
    public function validateEmail($email)
    {
        // Step 1: Basic format validation
        if (!$this->isValidFormat($email)) {
            return [
                'valid' => false,
                'reason' => 'Format email tidak valid'
            ];
        }

        // Step 2: Domain validation
        $domain_check = $this->validateDomain($email);
        if (!$domain_check['valid']) {
            return $domain_check;
        }

        // Step 3: Gmail specific validation (if Gmail)
        if ($this->isGmailAddress($email)) {
            $gmail_check = $this->validateGmailAddress($email);
            if (!$gmail_check['valid']) {
                return $gmail_check;
            }
        }

        // Step 4: Disposable email check
        $disposable_check = $this->checkDisposableEmail($email);
        if (!$disposable_check['valid']) {
            return $disposable_check;
        }

        // Step 5: Pattern-based fake email detection
        $pattern_check = $this->checkSuspiciousPatterns($email);
        if (!$pattern_check['valid']) {
            return $pattern_check;
        }

        return [
            'valid' => true,
            'reason' => 'Email valid'
        ];
    }

    /**
     * Basic email format validation
     */
    private function isValidFormat($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Domain validation with MX record check
     */
    private function validateDomain($email)
    {
        $domain = substr(strrchr($email, "@"), 1);

        // Check if domain exists and has MX record
        if (!checkdnsrr($domain, "MX") && !checkdnsrr($domain, "A")) {
            return [
                'valid' => false,
                'reason' => 'Domain email tidak valid atau tidak memiliki server email'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Check if email is Gmail address
     */
    private function isGmailAddress($email)
    {
        $domain = substr(strrchr($email, "@"), 1);
        $gmail_domains = ['gmail.com', 'googlemail.com'];
        return in_array(strtolower($domain), $gmail_domains);
    }

    /**
     * Advanced Gmail validation
     */
    private function validateGmailAddress($email)
    {
        $local_part = substr($email, 0, strpos($email, '@'));

        // Gmail specific rules
        // 1. Must be 6-30 characters
        if (strlen($local_part) < 6 || strlen($local_part) > 30) {
            return [
                'valid' => false,
                'reason' => 'Gmail address harus memiliki 6-30 karakter sebelum @'
            ];
        }

        // 2. Cannot start or end with dots
        if (substr($local_part, 0, 1) === '.' || substr($local_part, -1) === '.') {
            return [
                'valid' => false,
                'reason' => 'Gmail address tidak boleh dimulai atau diakhiri dengan titik'
            ];
        }

        // 3. Cannot have consecutive dots
        if (strpos($local_part, '..') !== false) {
            return [
                'valid' => false,
                'reason' => 'Gmail address tidak boleh memiliki titik berturut-turut'
            ];
        }

        // 4. Must contain at least one letter
        if (!preg_match('/[a-zA-Z]/', $local_part)) {
            return [
                'valid' => false,
                'reason' => 'Gmail address harus mengandung minimal satu huruf'
            ];
        }

        // 5. Check for suspicious patterns in Gmail
        if ($this->isSuspiciousGmailPattern($local_part)) {
            return [
                'valid' => false,
                'reason' => 'Pola email terdeteksi sebagai email palsu atau tidak valid'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Check for suspicious Gmail patterns
     */
    private function isSuspiciousGmailPattern($local_part)
    {
        $suspicious_patterns = [
            // Random character patterns
            '/^[a-z]{10,}$/',  // All lowercase, too long
            '/^[0-9]{8,}$/',   // All numbers, too long
            '/^[a-z]{3,5}[0-9]{5,}$/', // Short letters + many numbers

            // Keyboard patterns
            '/qwerty|asdf|zxcv|1234|abcd/',

            // Repeated patterns
            '/(.)\1{4,}/',  // Same character repeated 5+ times
            '/^(..)\1{3,}/', // Same 2 chars repeated 4+ times

            // Common fake patterns
            '/test|fake|dummy|sample|example|temp|trash/',

            // Random gibberish detection (consonant/vowel patterns)
            '/^[bcdfghjklmnpqrstvwxyz]{6,}$/', // Too many consonants
            '/^[aeiou]{4,}$/', // Too many vowels
        ];

        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, strtolower($local_part))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check against disposable email providers
     */
    private function checkDisposableEmail($email)
    {
        $domain = strtolower(substr(strrchr($email, "@"), 1));

        // List of known disposable email domains
        $disposable_domains = [
            '10minutemail.com',
            'tempmail.org',
            'guerrillamail.com',
            'mailinator.com',
            'yopmail.com',
            'temp-mail.org',
            'throwaway.email',
            'getnada.com',
            'maildrop.cc',
            'sharklasers.com',
            'grr.la',
            'guerrillamailblock.com'
        ];

        if (in_array($domain, $disposable_domains)) {
            return [
                'valid' => false,
                'reason' => 'Email sementara/disposable tidak diperbolehkan'
            ];
        }

        return ['valid' => true];
    }

    //  Check for suspicious patterns in any email

    private function checkSuspiciousPatterns($email)
    {
        $local_part = substr($email, 0, strpos($email, '@'));

        // Check for obviously fake patterns
        $fake_indicators = [
            // Too random/gibberish
            '/^[a-z]{15,}$/',  // Very long random letters
            '/^[a-z]{3,8}[a-z]{3,8}[a-z]{3,8}$/', // Repeated random segments

            // Keyboard mashing
            '/qwertyuiop|asdfghjkl|zxcvbnm/',
            '/poiuytrewq|lkjhgfdsa|mnbvcxz/',

            // Number sequences
            '/123456|654321|111111|000000/',

            // Common test patterns
            '/test123|admin123|user123|demo123/',
        ];

        foreach ($fake_indicators as $pattern) {
            if (preg_match($pattern, strtolower($local_part))) {
                return [
                    'valid' => false,
                    'reason' => 'Email terdeteksi sebagai email palsu atau testing'
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Advanced validation using external API (optional)
     */
    private function validateWithAPI($email)
    {
        // This would use external services like Hunter.io, ZeroBounce, etc.
        // For localhost development, we'll skip this
        return ['valid' => true];
    }
}
