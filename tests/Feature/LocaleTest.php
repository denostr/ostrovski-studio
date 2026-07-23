<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocaleTest extends TestCase
{
    public function test_explicit_language_switch_persists_the_choice_and_cleans_the_url(): void
    {
        $this->get('/?_lang=en')
            ->assertRedirect(url('/'))
            ->assertCookie('locale', 'en');
    }

    public function test_german_ip_visitors_are_redirected_to_de(): void
    {
        $this->withHeaders(['CF-IPCountry' => 'DE'])
            ->get('/')
            ->assertRedirect('/de');
    }

    public function test_german_ip_redirect_respects_an_explicit_english_choice(): void
    {
        $this->withCookie('locale', 'en')
            ->withHeaders(['CF-IPCountry' => 'DE'])
            ->get('/')
            ->assertOk();
    }

    public function test_visiting_de_persists_the_german_choice(): void
    {
        $this->get('/de')->assertCookie('locale', 'de');
    }

    public function test_visiting_de_does_not_overwrite_an_explicit_english_choice(): void
    {
        // Following a shared /de link must not flip a stored `en` choice
        // back to `de` — that would re-enable the geo-redirect against the
        // visitor's expressed preference.
        $this->withCookie('locale', 'en')
            ->get('/de/impressum')
            ->assertOk()
            ->assertCookieMissing('locale');
    }

    public function test_non_mirrored_routes_are_not_redirected(): void
    {
        $this->withHeaders(['CF-IPCountry' => 'DE'])
            ->get('/sitemap.xml')
            ->assertOk();
    }
}
