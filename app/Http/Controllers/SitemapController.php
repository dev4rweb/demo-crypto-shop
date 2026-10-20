<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $locales = config('localization.supported', ['en']);
        $default = (string) config('localization.default', 'en');
        $paths = ['/', '/about', '/catalog', '/login', '/privacy', '/terms'];
        $slugs = Product::query()->where('is_active', true)->pluck('slug');

        $urls = [];

        foreach ($locales as $locale) {
            $prefix = $locale === $default ? '' : '/'.$locale;

            foreach ($paths as $path) {
                $urls[] = url($prefix === '' && $path === '/' ? '/' : $prefix.$path);
            }

            foreach ($slugs as $slug) {
                $urls[] = url($prefix.'/catalog/'.$slug);
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach (array_unique($urls) as $loc) {
            $xml .= '  <url><loc>'.e($loc).'</loc></url>'."\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
