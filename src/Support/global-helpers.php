<?php

use A17\Localization\Localization as LocalizationFacade;
use A17\Localization\Services\Localization as LocalizationService;

if (!function_exists('localization')) {
    function localization(): LocalizationService
    {
        return LocalizationFacade::instance();
    }
}
