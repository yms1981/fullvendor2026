create table company_status_order
(
    id         int auto_increment
        primary key,
    cod_status int      default 0                   null,
    company_id int                                  null,
    name       varchar(50)                          null,
    email      longtext                             null,
    created_at datetime default current_timestamp() not null,
    updated_at datetime default current_timestamp() not null on update current_timestamp()
);

