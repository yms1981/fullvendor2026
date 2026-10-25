create table csvtemp_t
(
    id_csv   int auto_increment
        primary key,
    nombre   varchar(245)                          null,
    def      longtext collate utf8mb4_bin          null,
    reg_crea timestamp default current_timestamp() null
)
    charset = latin1;

