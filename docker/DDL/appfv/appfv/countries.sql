create table countries
(
    id        int auto_increment
        primary key,
    sortname  varchar(3)                         not null,
    name      varchar(150)                       not null,
    phonecode int                                not null,
    time_zone varchar(50) default 'America/Lima' not null
)
    charset = utf8mb3;

