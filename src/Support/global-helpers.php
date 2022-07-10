<?php

use Illuminate\Support\Collection;
use A17\Localization\Localization as LocalizationFacade;
use A17\Localization\Services\Localization as LocalizationService;

if (!function_exists('localization')) {
    function localization(): LocalizationService
    {
        return LocalizationFacade::instance();
    }
}

if (!function_exists('locales')) {
    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    function locales(): Collection
    {
        return LocalizationFacade::instance()->locales();
    }
}

if (!function_exists('locale')) {
    function locale(string|null $new = null): string|null
    {
        return LocalizationFacade::instance()->locale($new);
    }
}

if (!function_exists('_rf')) {
    /**
     * Route front helper. Returns a clean URL.
     */
    function _rf(string $name, array $parameters = [], bool $absolute = true): string
    {
        return LocalizationFacade::instance()
            ->routing()
            ->routeFront($name, $parameters, $absolute);
    }
}

if (!function_exists('_rf')) {
    function _rf_name(string $name): string
    {
        return LocalizationFacade::instance()
            ->routing()
            ->frontName($name);
    }
}

if (!function_exists('_rfqs')) {
    function _rfqs(string $name, array $parameters = [], bool $absolute = true): string
    {
        return LocalizationFacade::instance()
            ->routing()
            ->routeFrontWithQuerySigned($name, $parameters, $absolute);
    }
}

if (!function_exists('_rfq')) {
    function _rfq(string $name, array $parameters = [], bool $absolute = true, bool $signed = false): string
    {
        return LocalizationFacade::instance()
            ->routing()
            ->routeFrontWithQuery($name, $parameters, $absolute, $signed);
    }
}

if (!function_exists('_rf_current')) {
    function _rf_current(string $name): bool
    {
        return LocalizationFacade::instance()
            ->routing()
            ->routeIsCurrent($name);
    }
}
