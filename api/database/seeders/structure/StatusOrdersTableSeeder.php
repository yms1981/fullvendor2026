<?php

namespace Database\Seeders\structure;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class StatusOrdersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();

        \DB::table('status_orders')->truncate();

        \DB::table('status_orders')->insertOrIgnore(array (
            0 =>
            array (
                'id_status' => 1,
                'name_status_spanish' => 'Nuevo',
                'name_status_english' => 'New',
                'cod_status' => 0,
                'color' => '#1EA910',
                'disabled' => NULL,
                'sombrilla' => 'text-success font-weight-bold',
                'color_icono' => 'text-success',
                'tipo_d' => 'D',
                'cannot_edit' => NULL,
                'can_show_data_asigned' => NULL,
                'send_email' => 1,

            ),
            1 =>
            array (
                'id_status' => 2,
                'name_status_spanish' => 'Leido',
                'name_status_english' => 'Read',
                'cod_status' => 1,
                'color' => '#A9CCE3',
                'disabled' => NULL,
                'sombrilla' => NULL,
                'color_icono' => 'text-success',
                'tipo_d' => 'D',
                'cannot_edit' => NULL,
                'can_show_data_asigned' => NULL,
                'send_email' => 1,

            ),
            2 =>
            array (
                'id_status' => 3,
                'name_status_spanish' => 'Pendiente',
                'name_status_english' => 'Pending',
                'cod_status' => 2,
                'color' => '#D0ECE7',
                'disabled' => NULL,
                'sombrilla' => NULL,
                'color_icono' => 'text-success',
                'tipo_d' => 'D',
                'cannot_edit' => NULL,
                'can_show_data_asigned' => NULL,
                'send_email' => 1,

            ),
            3 =>
            array (
                'id_status' => 5,
                'name_status_spanish' => 'Despachado',
                'name_status_english' => 'Dispatched',
                'cod_status' => 3,
                'color' => '#ABEBC6',
                'disabled' => NULL,
                'sombrilla' => NULL,
                'color_icono' => 'text-success',
                'tipo_d' => 'D',
                'cannot_edit' => 1,
                'can_show_data_asigned' => NULL,
                'send_email' => 1,

            ),
            4 =>
            array (
                'id_status' => 6,
                'name_status_spanish' => 'Entregado',
                'name_status_english' => 'Delivered',
                'cod_status' => 4,
                'color' => '#FAE5D3',
                'disabled' => NULL,
                'sombrilla' => NULL,
                'color_icono' => 'text-success',
                'tipo_d' => 'D',
                'cannot_edit' => 1,
                'can_show_data_asigned' => NULL,
                'send_email' => 1,

            ),
            5 =>
            array (
                'id_status' => 7,
                'name_status_spanish' => 'Solicitud de Devolucion',
                'name_status_english' => 'Return Request',
                'cod_status' => 5,
                'color' => 'teal',
                'disabled' => NULL,
                'sombrilla' => NULL,
                'color_icono' => 'text-success',
                'tipo_d' => 'D',
                'cannot_edit' => NULL,
                'can_show_data_asigned' => NULL,
                'send_email' => 1,

            ),
            6 =>
            array (
                'id_status' => 8,
                'name_status_spanish' => 'Devuelto',
                'name_status_english' => 'Returned',
                'cod_status' => 6,
                'color' => '#00cff4',
                'disabled' => NULL,
                'sombrilla' => NULL,
                'color_icono' => 'text-success',
                'tipo_d' => 'D',
                'cannot_edit' => NULL,
                'can_show_data_asigned' => NULL,
                'send_email' => 1,

            ),
            7 =>
            array (
                'id_status' => 9,
                'name_status_spanish' => 'Cancelado',
                'name_status_english' => 'Canceled',
                'cod_status' => 7,
                'color' => '#CB4335',
                'disabled' => NULL,
                'sombrilla' => NULL,
                'color_icono' => 'text-success',
                'tipo_d' => 'D',
                'cannot_edit' => NULL,
                'can_show_data_asigned' => NULL,
                'send_email' => 1,

            ),
            8 =>
            array (
                'id_status' => 10,
                'name_status_spanish' => 'Aprobado',
                'name_status_english' => 'Approved',
                'cod_status' => 8,
                'color' => '#1EA910',
                'disabled' => NULL,
                'sombrilla' => 'text-success font-weight-bold',
                'color_icono' => 'text-success',
                'tipo_d' => 'D',
                'cannot_edit' => 1,
                'can_show_data_asigned' => NULL,
                'send_email' => 1,

            ),
            9 =>
            array (
                'id_status' => 11,
                'name_status_spanish' => 'Deposito Asignado',
                'name_status_english' => 'Warehouse Assigned',
                'cod_status' => 9,
                'color' => '#138D75',
                'disabled' => 1,
                'sombrilla' => NULL,
                'color_icono' => 'text-success',
                'tipo_d' => 'D',
                'cannot_edit' => 1,
                'can_show_data_asigned' => NULL,
                'send_email' => 1,

            ),
            10 =>
            array (
                'id_status' => 12,
                'name_status_spanish' => 'Parcialmente Completado',
                'name_status_english' => 'Partially Completed',
                'cod_status' => 10,
                'color' => '#2E86C1',
                'disabled' => NULL,
                'sombrilla' => NULL,
                'color_icono' => 'text-success',
                'tipo_d' => 'D',
                'cannot_edit' => NULL,
                'can_show_data_asigned' => NULL,
                'send_email' => 1,

            ),
            11 =>
            array (
                'id_status' => 13,
                'name_status_spanish' => 'Deposito Completado',
                'name_status_english' => 'Warehouse Completed',
                'cod_status' => 11,
                'color' => '#7D3C98',
                'disabled' => NULL,
                'sombrilla' => 'text-purple font-weight-bold',
                'color_icono' => 'text-success',
                'tipo_d' => 'D',
                'cannot_edit' => 1,
                'can_show_data_asigned' => NULL,
                'send_email' => 1,

            ),
            12 =>
            array (
                'id_status' => 14,
                'name_status_spanish' => 'Completado',
                'name_status_english' => 'Completed',
                'cod_status' => 12,
                'color' => '#1EA910',
                'disabled' => NULL,
                'sombrilla' => NULL,
                'color_icono' => 'text-success',
                'tipo_d' => 'D',
                'cannot_edit' => 1,
                'can_show_data_asigned' => NULL,
                'send_email' => 1,

            ),
            13 =>
            array (
                'id_status' => 15,
                'name_status_spanish' => 'Rechazado',
                'name_status_english' => 'Rejected',
                'cod_status' => 13,
                'color' => '#CB4335',
                'disabled' => NULL,
                'sombrilla' => 'text-dark font-weight-bold strikethrough',
                'color_icono' => 'text-success',
                'tipo_d' => 'D',
                'cannot_edit' => NULL,
                'can_show_data_asigned' => NULL,
                'send_email' => 1,

            ),
            14 =>
            array (
                'id_status' => 16,
                'name_status_spanish' => 'Retornado por deposito',
                'name_status_english' => 'Returned by Warehouse',
                'cod_status' => 14,
                'color' => '#CB4335',
                'disabled' => NULL,
                'sombrilla' => 'text-dark font-weight-bold',
                'color_icono' => 'text-success',
                'tipo_d' => 'D',
                'cannot_edit' => NULL,
                'can_show_data_asigned' => NULL,
                'send_email' => 1,

            ),
            15 =>
            array (
                'id_status' => 17,
                'name_status_spanish' => 'Nota de Crédito',
                'name_status_english' => 'Credit Note',
                'cod_status' => 15,
                'color' => 'red',
                'disabled' => 1,
                'sombrilla' => 'text-danger font-weight-bold',
                'color_icono' => 'text-danger',
                'tipo_d' => 'C',
                'cannot_edit' => NULL,
                'can_show_data_asigned' => NULL,
                'send_email' => NULL,

            ),
            16 =>
            array (
                'id_status' => 18,
                'name_status_spanish' => 'En Proceso',
                'name_status_english' => 'In Proccess',
                'cod_status' => 16,
                'color' => '#D0ECE7',
                'disabled' => NULL,
                'sombrilla' => '',
                'color_icono' => '',
                'tipo_d' => 'D',
                'cannot_edit' => NULL,
                'can_show_data_asigned' => NULL,
                'send_email' => 1,

            ),
            17 =>
            array (
                'id_status' => 19,
                'name_status_spanish' => 'Compras',
                'name_status_english' => 'Shopping',
                'cod_status' => -1,
                'color' => NULL,
                'disabled' => NULL,
                'sombrilla' => NULL,
                'color_icono' => NULL,
                'tipo_d' => 'D',
                'cannot_edit' => NULL,
                'can_show_data_asigned' => NULL,
                'send_email' => NULL,

            ),
        ));
        Schema::enableForeignKeyConstraints();

    }
}
