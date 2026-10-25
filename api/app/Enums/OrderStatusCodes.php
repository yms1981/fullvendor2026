<?php
namespace App\Enums;
enum OrderStatusCodes:int {
    case SHOPPING = -1;
    case PLACED = 0; //New

    case READ = 1;
    case PENDING = 2;
    case DISPATCHED = 3;
    case DELIVERED = 4;
    case RETURN_REQUEST = 5;
    case RETURNED = 6;
    case CANCELED = 7;
    case APPROVED = 8;
    case WAREHOUSE_ASSIGNED = 9;
    case PARTIALLY_COMPLETED = 10;
    case WAREHOUSE_COMPLETED = 11;

    case COMPLETED = 12;
    case REJECTED = 13;
    case WAREHOUSE_RETURNED = 14;
    //case CREDIT_NOTE = 15;
    case IN_PROCESS = 16;

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function forStar(): array
    {
        return [
            self::DISPATCHED->value,
            self::DELIVERED->value,
            self::APPROVED->value,
            self::COMPLETED->value,
        ];
    }
}
