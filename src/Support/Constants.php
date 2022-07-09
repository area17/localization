<?php

namespace A17\Localization\Support;

class Constants
{
    // ----- by milliseconds

    public const MILLISECOND = 1;

    public const MS_SECOND = self::MILLISECOND * 1000;

    public const MS_MINUTE = self::MS_SECOND * 60;

    public const MS_HOUR = self::MS_MINUTE * 60;

    public const MS_DAY = self::MS_HOUR * 24;

    public const MS_WEEK = self::MS_DAY * 7;

    // ----- by seconds

    public const SECOND = 1;

    public const MINUTE = self::SECOND * 60;

    public const HOUR = self::MINUTE * 60;

    public const DAY = self::HOUR * 24;

    public const WEEK = self::DAY * 7;

    public const MONTH = self::DAY * 30;

    public const YEAR = self::MONTH * 12;
}
