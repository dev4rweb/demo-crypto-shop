<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class AboutController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('About', [
            'seo' => page_seo(__('app.about.meta_title'), __('app.about.meta_description')),
        ]);
    }
}
