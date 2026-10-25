create table terms_of_sales
(
    term_id     bigint auto_increment
        primary key,
    language_id int               null,
    company_id  int               null,
    user_id     int               null,
    name        varchar(255)      null,
    created_at  datetime          null,
    term_status tinyint default 1 not null,
    `default`   int(8)            null
);

