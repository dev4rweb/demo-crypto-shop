<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    public function privacy(): Response
    {
        return Inertia::render('Legal', [
            'page' => 'privacy',
            'seo' => page_seo(__('app.legal.privacy.meta_title'), __('app.legal.privacy.meta_description')),
        ]);
    }

    public function terms(): Response
    {
        return Inertia::render('Legal', [
            'page' => 'terms',
            'seo' => page_seo(__('app.legal.terms.meta_title'), __('app.legal.terms.meta_description')),
        ]);
    }
}
