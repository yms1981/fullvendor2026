create table csvtemp_t
(
    id_csv   int auto_increment
        primary key,
    nombre   varchar(245)                          null,
    def      longtext collate utf8mb4_bin          null
        check (json_valid(`def`)),
    reg_crea timestamp default current_timestamp() null
)
    charset = latin1;

