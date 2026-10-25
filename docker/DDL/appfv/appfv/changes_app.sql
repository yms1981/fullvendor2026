create table changes_app
(
    id         bigint(19) auto_increment
        primary key,
    company_id bigint(19) default 0                   not null,
    created    datetime   default current_timestamp() not null,
    `read`     int(10)    default 0                   not null,
    email      varchar(300)                           null,
    user_id    bigint                                 null
)
    collate = utf8mb4_unicode_ci;

