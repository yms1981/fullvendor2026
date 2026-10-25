create table consoleusers
(
    id       bigint(19) auto_increment
        primary key,
    email    varchar(500) not null,
    password varchar(500) null,
    rol      varchar(500) null
)
    collate = utf8mb4_unicode_ci;

create index email
    on consoleusers (email);

