create table `keys`
(
    id             int auto_increment
        primary key,
    user_id        int                  not null,
    `key`          varchar(40)          not null,
    level          int(2)               not null,
    ignore_limits  tinyint(1) default 0 not null,
    is_private_key tinyint(1) default 0 not null,
    ip_addresses   text                 null,
    date_created   datetime             not null
)
    charset = utf8mb3;

