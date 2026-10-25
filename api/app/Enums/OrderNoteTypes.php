<?php

namespace App\Enums;

enum OrderNoteTypes:string
{
    case Order = 'order';
    case Shipping = 'shipping';
    case Internal = 'internal';
    case Delivery = 'delivery';

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }
}
