<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * The XML sitemap — the home page and the legal pages, in every
     * configured locale.
     */
    public function __invoke(): Response
    {
        $urls = [];

        foreach (config('ostrovski.locales') as $locale) {
            $urls[] = loc_route('home', [], $locale);

            foreach (config('ostrovski.legal') as $page) {
                $urls[] = loc_route('legal', ['page' => $page], $locale);
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.e($url).'</loc></url>'."\n";
        }

        $xml .= '</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
