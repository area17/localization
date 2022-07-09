<?php

namespace A17\Localization;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use A17\Localization\Services\Localization as LocalizationService;

/**
 * @method static LocalizationService instance()
 * @method static LocalizationService setRequest(Request $request)
 * @method static Request getRequest()
 * @method static bool enabled()
 * @method static LocalizationService setConfig()
 **/
class Localization extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'a17.localization.service';
    }
}
