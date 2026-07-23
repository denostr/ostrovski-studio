<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class LegalController extends Controller
{
    /**
     * Render a legally required page (Impressum, AGB, Datenschutz). The
     * {page} slug is already validated by the route constraint, so it can
     * be trusted to address a known block in the `legal` translation file.
     * The active locale is applied by the SetLocale middleware.
     */
    public function __invoke(string $page): View
    {
        return view('legal', ['page' => $page]);
    }
}
