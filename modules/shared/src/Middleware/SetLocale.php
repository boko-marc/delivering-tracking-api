<?php

declare(strict_types=1);

namespace Module\Shared\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next): mixed
    {
        $defaultLocale = config('app.locale');
        $defaultLocale = is_string($defaultLocale) ? $defaultLocale : 'en';
        $locale = $request->header('X-Language', $defaultLocale);

        if (! in_array($locale, ['en', 'fr'], true)) {
            $locale = $defaultLocale;
        }

        App::setLocale($locale);

        return $next($request);
    }
}
