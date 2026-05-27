<?php

namespace Module\Shared\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{

    public function handle(Request $request, Closure $next)
    {
        $locale = $request->header('X-Language', config('app.locale'));

        if (! in_array($locale, ['en', 'fr'])) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
