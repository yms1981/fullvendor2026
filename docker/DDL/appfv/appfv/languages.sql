create table languages
(
    language_id int auto_increment
        primary key,
    name        char(49)          null,
    iso_639_1   char(2)           null,
    created_at  datetime          null,
    status      tinyint default 1 not null,
    type        varchar(50)       null,
    `default`   int(5)            null
);

