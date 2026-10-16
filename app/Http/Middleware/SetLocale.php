<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('localization.supported', ['en']);
        $default = config('localization.default', 'en');
        $hidden = config('localization.hidden_in_selector', []);

        $routeLocale = $request->route('locale');

        if (is_string($routeLocale) && in_array($routeLocale, $supported, true)) {
            $locale = $routeLocale;
            if ($request->hasSession()) {
                $request->session()->put('locale', $locale);
            }
        } elseif ($request->hasSession() && in_array($request->session()->get('locale'), $supported, true)) {
            $locale = $request->session()->get('locale');
        } else {
            $locale = $this->fromAcceptLanguage($request, $supported, $hidden) ?? $default;
        }

        App::setLocale($locale);
        URL::defaults(['locale' => is_string($routeLocale) ? $routeLocale : null]);

        return $next($request);
    }

    /**
     * @param  list<string>  $supported
     * @param  list<string>  $hidden
     */
    private function fromAcceptLanguage(Request $request, array $supported, array $hidden): ?string
    {
        $visible = array_values(array_diff($supported, $hidden));

        foreach ($request->getLanguages() as $language) {
            $base = strtolower(substr(str_replace('_', '-', $language), 0, 2));

            if (in_array($base, $visible, true)) {
                return $base;
            }
        }

        return null;
    }
}
