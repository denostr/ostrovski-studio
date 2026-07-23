<?php

namespace App\Http\Requests;

use App\Rules\Turnstile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Collapse whitespace runs (including newlines) in the name so it can
     * never carry line breaks into a mail header if a future change
     * interpolates it there.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim((string) preg_replace('/\s+/u', ' ', $this->input('name')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'service' => ['required', 'string', Rule::in(config('ostrovski.services'))],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:190'],
            'message' => ['required', 'string', 'max:3000'],
            // Anti-spam: an invisible field only bots fill in (`prohibited`
            // fails on any non-empty value), plus the Cloudflare Turnstile
            // token. The token must be `required` whenever the widget is
            // rendered — Laravel skips non-implicit rules on absent
            // attributes, so without this a bot could bypass the captcha by
            // simply omitting the field.
            'website' => ['prohibited'],
            'cf-turnstile-response' => [
                config('services.turnstile.site_key') ? 'required' : 'nullable',
                new Turnstile,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'service.required' => __('enquiry.errors.service'),
            'service.in' => __('enquiry.errors.service'),
            'name.required' => __('enquiry.errors.name'),
            'email.required' => __('enquiry.errors.email'),
            'email.email' => __('enquiry.errors.email_valid'),
            'message.required' => __('enquiry.errors.message'),
            'website.prohibited' => __('enquiry.errors.spam'),
            'cf-turnstile-response.required' => __('enquiry.errors.captcha'),
        ];
    }
}
