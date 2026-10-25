create table categories
(
    cat_id              bigint auto_increment
        primary key,
    category_id         bigint           null,
    language_id         int    default 1 null,
    company_id          int    default 0 not null comment 'PK of companies',
    category_name       varchar(255)     null,
    images              varchar(255)     null,
    category_status     int    default 1 not null,
    category_created_at datetime         null,
    id_kor              bigint default 0 null,
    category_default    int              null
);

