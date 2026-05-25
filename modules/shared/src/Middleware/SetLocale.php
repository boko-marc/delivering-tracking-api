<?php

namespace Module\Shared\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->header('X-Language');

        if (! $locale) {
            $locale = config('app.locale');
        }

        $supported = ['en', 'fr'];
        if (! in_array($locale, $supported)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
