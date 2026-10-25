create table gpstracker
(
    id         bigint(19) auto_increment
        primary key,
    company_id bigint(19) default 0                   not null,
    user_id    bigint(19) default 0                   not null,
    longitude  float      default 0                   not null,
    latitude   float      default 0                   not null,
    created    timestamp  default current_timestamp() not null,
    login      bigint                                 null,
    address    varchar(600)                           null,
    deviceinfo text       default '-'                 null
)
    collate = utf8mb4_unicode_ci;

