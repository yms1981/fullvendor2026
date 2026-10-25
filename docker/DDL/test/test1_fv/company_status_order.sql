create table company_status_order
(
    id         int auto_increment
        primary key,
    cod_status int      default 0                   not null,
    company_id int      default 0                   not null,
    name       varchar(50)                          null,
    email      longtext                             null,
    created_at datetime default current_timestamp() not null,
    `update`   datetime default current_timestamp() not null on update current_timestamp()
)
    collate = utf8mb4_unicode_ci;

