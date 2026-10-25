create table consoleconfig
(
    id         bigint(19) auto_increment
        primary key,
    token      varchar(500) null,
    company_id bigint(19)   null,
    username   varchar(200) null,
    password   varchar(200) null,
    email      varchar(200) null,
    address    varchar(500) null,
    telephone  varchar(500) null,
    app        varchar(500) null,
    rol        varchar(500) null,
    constraint token
        unique (token)
);

