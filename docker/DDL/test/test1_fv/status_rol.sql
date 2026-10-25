create table status_rol
(
    id         int auto_increment
        primary key,
    company_id int                                  null,
    rol_id     int                                  null,
    created_at datetime default current_timestamp() null,
    update_at  datetime default current_timestamp() null on update current_timestamp(),
    id_status  int                                  null
)
    collate = utf8mb4_unicode_ci;

