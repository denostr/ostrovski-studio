<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    public function test_home_renders_in_english(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Katya')
            ->assertSee(__('services.headline'))
            ->assertSee('hreflang="de"', false);
    }

    public function test_home_renders_in_german(): void
    {
        $this->get('/de')
            ->assertOk()
            ->assertSee('Künstlerin')
            ->assertSee('Mit Ostrovski arbeiten');
    }

    public function test_legal_pages_render_in_both_locales(): void
    {
        foreach (config('ostrovski.legal') as $page) {
            $this->get('/'.$page)->assertOk();
            $this->get('/de/'.$page)->assertOk();
        }
    }

    public function test_unknown_legal_slug_is_a_404(): void
    {
        $this->get('/kontakt')->assertNotFound();
    }

    public function test_services_redirects_to_the_home_anchor(): void
    {
        $this->get('/services')->assertRedirect(url('/').'#services');
        $this->get('/de/services')->assertRedirect(url('/de').'#services');
    }

    public function test_the_anchor_contract_of_the_home_page_holds(): void
    {
        // The `/services` redirect and the topbar links depend on these ids
        // by bare string — this guards the contract against a refactor
        // silently dropping one.
        $this->get('/')
            ->assertSee('id="services"', false)
            ->assertSee('id="services-dj"', false)
            ->assertSee('id="services-show"', false);
    }

    public function test_every_configured_service_has_copy_in_every_locale(): void
    {
        // A service added to config without lang keys degrades silently —
        // __() would render the raw key in the modal title and the
        // owner-facing email subject.
        foreach (config('ostrovski.locales') as $locale) {
            foreach (config('ostrovski.services') as $service) {
                foreach (['kicker', 'title', 'desc'] as $key) {
                    $this->assertTrue(
                        \Lang::hasForLocale("services.$service.$key", $locale),
                        "Missing lang key services.$service.$key for locale $locale",
                    );
                }
            }
        }
    }

    public function test_sitemap_lists_every_page_in_every_locale(): void
    {
        $response = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $response->assertSee(url('/'));
        $response->assertSee(url('/de'));

        foreach (config('ostrovski.legal') as $page) {
            $response->assertSee(url('/'.$page));
            $response->assertSee(url('/de/'.$page));
        }
    }

    public function test_robots_txt_points_at_the_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: '.url('/sitemap.xml'));
    }

    public function test_security_headers_are_present(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
