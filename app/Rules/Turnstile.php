<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifies a Cloudflare Turnstile token. Verification is off only when
 * NEITHER key is configured (local development); a half-configured pair
 * fails closed and logs loudly — a lost secret must never silently
 * disable the captcha while the widget keeps rendering.
 */
class Turnstile implements DataAwareRule, ValidationRule
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /** @var array<string, mixed> */
    private array $data = [];

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.turnstile.secret_key');
        $siteKey = config('services.turnstile.site_key');

        if (blank($secret) && blank($siteKey)) {
            return;
        }

        if (blank($secret) || blank($siteKey)) {
            Log::error('Turnstile misconfigured: site_key and secret_key must both be set — failing the captcha check closed.');
            $fail('enquiry.errors.captcha')->translate();

            return;
        }

        // The honeypot has already condemned this submission — skip the
        // external siteverify round-trip, validation fails anyway.
        if (filled($this->data['website'] ?? null)) {
            return;
        }

        try {
            $verified = Http::asForm()
                ->connectTimeout(2)
                ->timeout(5)
                ->post(self::VERIFY_URL, [
                    'secret' => $secret,
                    'response' => is_string($value) ? $value : '',
                ])
                ->json('success', false);
        } catch (ConnectionException) {
            $fail('enquiry.errors.captcha')->translate();

            return;
        }

        if ($verified !== true) {
            $fail('enquiry.errors.captcha')->translate();
        }
    }
}
