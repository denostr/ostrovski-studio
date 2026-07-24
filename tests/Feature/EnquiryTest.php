<?php

namespace Tests\Feature;

use App\Mail\EnquiryReceived;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EnquiryTest extends TestCase
{
    /**
     * @return array{service: string, name: string, phone: string, email: string, message: string}
     */
    private function validPayload(): array
    {
        return [
            'service' => 'dj',
            'name' => 'Test User',
            'phone' => '+49 123 456789',
            'email' => 'test@example.com',
            'message' => 'Hello, I would like to book a DJ set.',
            'consent' => true,
        ];
    }

    public function test_a_valid_enquiry_sends_the_notification_email(): void
    {
        Mail::fake();

        $this->postJson('/enquiry', $this->validPayload())
            ->assertOk()
            ->assertJson(['ok' => true]);

        Mail::assertSent(EnquiryReceived::class, function (EnquiryReceived $mail): bool {
            return $mail->hasTo(config('ostrovski.email'))
                && $mail->hasReplyTo('test@example.com')
                && $mail->enquiry['service'] === 'dj'
                && $mail->enquiry['email'] === 'test@example.com';
        });
    }

    public function test_the_german_route_works_too(): void
    {
        Mail::fake();

        $this->postJson('/de/enquiry', $this->validPayload())->assertOk();

        Mail::assertSent(EnquiryReceived::class);
    }

    public function test_an_enquiry_without_consent_is_rejected(): void
    {
        Mail::fake();

        $this->postJson('/enquiry', [...$this->validPayload(), 'consent' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['consent']);

        Mail::assertNothingSent();
    }

    public function test_missing_fields_fail_validation(): void
    {
        Mail::fake();

        $this->postJson('/enquiry', ['service' => 'dj'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'message']);

        Mail::assertNothingSent();
    }

    public function test_an_unknown_service_fails_validation(): void
    {
        Mail::fake();

        $this->postJson('/enquiry', [...$this->validPayload(), 'service' => 'catering'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service']);

        Mail::assertNothingSent();
    }

    public function test_an_invalid_email_fails_validation(): void
    {
        Mail::fake();

        $this->postJson('/enquiry', [...$this->validPayload(), 'email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        Mail::assertNothingSent();
    }

    public function test_a_filled_honeypot_is_rejected(): void
    {
        Mail::fake();

        $this->postJson('/enquiry', [...$this->validPayload(), 'website' => 'https://spam.example'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['website']);

        Mail::assertNothingSent();
    }

    public function test_a_missing_turnstile_token_fails_when_the_widget_is_configured(): void
    {
        Mail::fake();
        config(['services.turnstile.site_key' => 'test-site-key', 'services.turnstile.secret_key' => 'test-secret']);

        // Absent attributes skip non-implicit rules, so the token must be
        // `required` whenever the widget renders — a bot omitting the field
        // entirely must not bypass the captcha.
        $this->postJson('/enquiry', $this->validPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cf-turnstile-response']);

        Mail::assertNothingSent();
    }

    public function test_a_half_configured_turnstile_fails_closed(): void
    {
        Mail::fake();
        config(['services.turnstile.site_key' => 'test-site-key', 'services.turnstile.secret_key' => null]);

        // A lost secret must not silently disable verification while the
        // widget keeps rendering — the rule fails closed and logs.
        $this->postJson('/enquiry', [...$this->validPayload(), 'cf-turnstile-response' => 'some-token'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cf-turnstile-response']);

        Mail::assertNothingSent();
    }

    public function test_a_mailer_failure_is_reported_as_an_error(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $this->postJson('/enquiry', $this->validPayload())
            ->assertStatus(500)
            ->assertJson(['ok' => false]);
    }

    public function test_the_endpoint_is_rate_limited(): void
    {
        Mail::fake();

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/enquiry', $this->validPayload())->assertOk();
        }

        $this->postJson('/enquiry', $this->validPayload())->assertStatus(429);
    }
}
