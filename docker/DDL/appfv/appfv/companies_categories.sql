create table companies_categories
(
    category_id         bigint auto_increment
        primary key,
    category_name       varchar(255)      null,
    category_status     tinyint default 1 null,
    category_created_at datetime          null
);

