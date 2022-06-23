<?php

declare(strict_types=1);

namespace Bayram\SEO;

use ArchTech\SEO\Commands\GenerateFaviconsCommand;
use Illuminate\Support\ServiceProvider;
use ImLiam\BladeHelper\BladeHelperServiceProvider;
use ImLiam\BladeHelper\Facades\BladeHelper;

class SEOServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('seo', SEOManager::class);
        $this->app->register(BladeHelperServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../assets/views', 'seo');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateFaviconsCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../assets/views' => resource_path('views/vendor/seo'),
        ], 'seo-views');

        BladeHelper::directive('seo', function (...$args) {
            // Flipp daha fazla argümanı destekler
            if ($args[0] === 'flipp') {
                array_shift($args);

                return seo()->flipp(...$args);
            }

// İki bağımsız değişken, bir değer ayarladığımızı gösterir, ör. `@seo('başlık', 'foo') 
            if (count($args) === 2) {
                return seo()->set($args[0], $args[1]);
            }

// Bir dizi, hiçbir şey döndürmediğimiz anlamına gelir, ör. `@seo(['başlık' => 'foo'])
            if (is_array($args[0])) {
                seo($args[0]);

                return null;
            }

// Tek bir değer, bir değer getirdiğimiz anlamına gelir, ör. `@seo('başlık')

            return seo()->get($args[0]);
        });
    }
}
