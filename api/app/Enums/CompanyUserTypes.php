<?php
namespace App\Enums;
enum CompanyUserTypes:string {
    case Customer = 'customer';
    case Seller = 'seller';
    case Manager = 'manager';
    case Warehouse = 'warehouse';

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }
}
