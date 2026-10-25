<?php

namespace App\Enums;

enum AddressCustomerTypes:string
{
    case Commercial = 'commercial';
    case Dispatch = 'dispatch';
    case CommercialDelivery = 'commercial_delivery';

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }
}
