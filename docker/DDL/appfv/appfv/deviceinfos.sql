create table deviceinfos
(
    deviceinfo  varchar(200)                          not null
        primary key,
    so          varchar(200)                          not null,
    version     varchar(200)                          not null,
    created     date      default curdate()           not null,
    updated_at  timestamp default current_timestamp() null,
    versionname varchar(300)                          null
)
    collate = utf8mb4_unicode_ci;

