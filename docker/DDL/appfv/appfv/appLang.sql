create table appLang
(
    id      bigint auto_increment
        primary key,
    class   varchar(200) default '0' not null,
    control varchar(200) default '0' not null,
    eng     varchar(200) default '0' not null,
    spa     varchar(200) default '0' not null
)
    collate = utf8mb4_unicode_ci;

