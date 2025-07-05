<?php

namespace App\Enums;

enum ReasonStatus: string
{
    use EnumValues;

    case Refused = 'refused';
    case NotResponding = 'not_responding';
    case DefectiveProduct = 'defective_product';
}
