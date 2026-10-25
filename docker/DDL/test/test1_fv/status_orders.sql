create table status_orders
(
    id_status             int auto_increment
        primary key,
    name_status_spanish   varchar(255) null,
    name_status_english   varchar(255) null,
    cod_status            int          null,
    color                 varchar(255) null,
    disabled              int(8)       null,
    sombrilla             varchar(255) null,
    color_icono           varchar(50)  null,
    tipo_d                varchar(50)  null,
    cannot_edit           int(5)       null,
    can_show_data_asigned int          null,
    send_email            int          null
)
    charset = latin1;

