create table rol
(
    rol_id        int auto_increment
        primary key,
    company_id    int                                  null,
    name          varchar(50)                          null,
    created_date  datetime default current_timestamp() null,
    modified_date datetime default current_timestamp() null on update current_timestamp(),
    is_active     int      default 0                   null,
    deleted_at    datetime                             null
)
    collate = utf8mb4_unicode_ci;

