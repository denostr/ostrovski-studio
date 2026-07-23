<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * The single-page OSTROVSKI site: hero, the about longread and the
     * services section with the enquiry modals. All copy comes from the
     * translation files; the active locale is applied by the SetLocale
     * middleware.
     */
    public function __invoke(): View
    {
        return view('home');
    }
}
