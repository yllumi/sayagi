<?php

namespace Yllumi\Sayagi\libraries;

/**
 * reCAPTCHA helper — supports v2 (checkbox), v3 (score-based), and off.
 *
 * Reads configuration from the user setting group.
 *
 * Usage (frontend):
 *   <?php if (Recaptcha::isEnabled()): ?>
 *     <script src="<?= Recaptcha::scriptUrl() ?>" async defer></script>
 *   <?php endif; ?>
 *
 * Usage (backend):
 *   if (!Recaptcha::verify($token)) { … failed … }
 */
class Recaptcha
{
    /**
     * Return the configured reCAPTCHA version: 'off', 'v2', or 'v3'.
     */
    public static function version(): string
    {
        return setting('user.recaptcha_version', 'v3');
    }

    /**
     * Whether reCAPTCHA is enabled (not 'off').
     */
    public static function isEnabled(): bool
    {
        return self::version() !== 'off';
    }

    /**
     * Return the reCAPTCHA site key from settings, or empty string.
     */
    public static function siteKey(): string
    {
        return setting('user.recaptcha_site_key') ?: '';
    }

    /**
     * Return the appropriate reCAPTCHA script URL based on the version.
     * Returns empty string when no site key is configured.
     */
    public static function scriptUrl(): string
    {
        $key = self::siteKey();
        if ($key === '') {
            return '';
        }

        if (self::version() === 'v3') {
            return 'https://www.google.com/recaptcha/api.js?render=' . urlencode($key);
        }

        // v2
        return 'https://www.google.com/recaptcha/api.js';
    }

    /**
     * Verify a reCAPTCHA token against Google.
     *
     * - When version is 'off', always returns true (skip check).
     * - When version is 'v3', checks both success and minimum score.
     * - When version is 'v2', checks success only.
     *
     * @param  string  $token    The token from the frontend.
     * @param  float   $minScore Minimum acceptable score for v3 (0.0 – 1.0). Default 0.5.
     * @return bool
     */
    public static function verify(string $token, float $minScore = 0.5): bool
    {
        $version = self::version();

        // Skip check entirely when reCAPTCHA is disabled
        if ($version === 'off') {
            return true;
        }

        $secretKey = setting('user.recaptcha_secret_key') ?: '';
        if ($secretKey === '' || $token === '') {
            return false;
        }

        $recaptcha = new \ReCaptcha\ReCaptcha($secretKey);

        if ($version === 'v3') {
            $recaptcha->setScoreThreshold($minScore);
        }

        $response = $recaptcha->verify($token, request()->getRealIp() ?: null);

        return $response->isSuccess();
    }
}
