<?php

declare(strict_types=1);

namespace Module\Shared\Providers;

use Illuminate\Support\ServiceProvider;

class SharedServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        // Register shared services or bindings here if needed.
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'shared');
    }
}
