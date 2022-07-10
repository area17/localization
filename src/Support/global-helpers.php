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
