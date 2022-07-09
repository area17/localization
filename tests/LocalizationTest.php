<?php

namespace A17\Localization\Tests;

use A17\Localization\Localization;
use A17\Localization\CacheControl;
use A17\Localization\ServiceProvider;
use A17\Localization\Support\Constants;

class Localization extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config(['localization.enabled' => true]);

        Localization::setRequest(new \Illuminate\Http\Request());
    }
}
