<?php

use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
 | SEO endpoints — served at the root, no locale prefix.
 */
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('robots.txt', function () {
    $body = implode("\n", [
        'User-agent: *',
        'Allow: /',
        '',
        'Sitemap: '.url('/sitemap.xml'),
    ])."\n";

    return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('robots');

/*
 | OSTROVSKI public site. English is the default locale and lives at the
 | root. German lives under the `/de` prefix, and its route names carry a
 | `de.` prefix — so a single set of controllers serves both, and the
 | `loc_route()` helper resolves the right URL for the active locale.
 */
$publicRoutes = function (): void {
    Route::get('/', HomeController::class)->name('home');

    /*
     | The services anchor as a stable URL — the Heels On site links here
     | via its OSTROVSKI_SERVICES_URL env. Will become a set of dedicated
     | service pages once their screens land in the design.
     */
    Route::get('/services', fn () => redirect(loc_route('home').'#services'))
        ->name('services');

    /*
     | Legally required pages (Impressum, AGB, Datenschutz). The {page}
     | slug is constrained to the configured set, so an unknown slug yields
     | a 404 rather than reaching the controller. Slugs stay identical
     | across locales — they are established German legal terms.
     */
    Route::get('/{page}', LegalController::class)
        ->whereIn('page', config('ostrovski.legal'))
        ->name('legal');

    /*
     | Enquiry form submissions (AJAX, JSON). Rate-limited: this is a
     | public, unauthenticated endpoint that sends email.
     */
    Route::post('enquiry', EnquiryController::class)
        ->middleware('throttle:10,1')
        ->name('enquiry');
};

// English — default locale, served at the root.
Route::group([], $publicRoutes);

// German — `/de` URL prefix, `de.` route-name prefix.
Route::prefix('de')->name('de.')->group($publicRoutes);
