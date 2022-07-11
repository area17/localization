<?php

namespace A17\Localization\Support;

use Throwable;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use A17\Localization\Localization;
use Illuminate\Support\Facades\Log;

class Helpers
{
    public static function debug(string|array|null $data = null): bool
    {
        $debugIsOn = config('localization.debug');

        if (!$debugIsOn) {
            return false;
        }

        if (blank($data)) {
            return $debugIsOn;
        }

        if (!is_string($data)) {
            $data = json_encode($data);

            $data = $data === false ? '' : $data;
        }

        Log::debug('[LOCALIZATION] ' . $data);

        return $debugIsOn;
    }

    public static function loadGlobalHelpers(): void
    {
        require __DIR__ . '/global-helpers.php';
    }
}
