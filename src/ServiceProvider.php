<?php

namespace A17\Localization;

use A17\Localization\Support\Helpers;
use A17\Localization\Services\Localization;
use A17\Localization\Services\BaseService;
use A17\Localization\Services\CacheControl;
use A17\Localization\Localization as LocalizationFacade;
use A17\Localization\Exceptions\Localization as Localizationxception;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    protected array $config;

    public function boot(): void
    {
        $this->publishConfig();

        $this->loadHelpers();
    }

    public function register(): void
    {
        $this->mergeConfig();

        $this->configureContainer();
    }

    public function publishConfig(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../config/localization.php' => config_path(
                    'localization.php',
                ),
            ],
            'config',
        );

        $this->config = config('localization');
    }

    protected function mergeConfig(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/localization.php',
            'localization',
        );
    }

    public function configureContainer(): void
    {
        $this->app->singleton('a17.localization.service', function ($app) {
            return (new Localization())->setConfig($this->config);
        });
    }

    public function loadHelpers(): void
    {
        if ($this->config['enabled']) {
            Helpers::loadGlobalHelpers();
        }
    }
}
