create table gpslog
(
    id         bigint auto_increment
        primary key,
    company_id bigint    default 0                   not null,
    user_id    bigint    default 0                   not null,
    login      bigint    default 1                   not null,
    infotext   text                                  not null,
    created    timestamp default current_timestamp() not null
)
    collate = utf8mb4_unicode_ci;

