create table settings
(
    settingId bigint auto_increment
        primary key,
    logo      varchar(500) null,
    favicon   varchar(500) null,
    title     varchar(500) null,
    address   varchar(500) null,
    email     varchar(500) null,
    phone     varchar(500) null,
    website   varchar(255) null,
    facebook  tinytext     null,
    twitter   tinytext     null,
    linkedin  tinytext     null,
    instagram tinytext     null
)
    charset = latin1;

