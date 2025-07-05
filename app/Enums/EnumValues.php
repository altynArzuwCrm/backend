<?php

namespace App\Enums;

trait EnumValues
{
    public static function values()
    {
        return array_column(self::cases(), 'value');
    }
}
