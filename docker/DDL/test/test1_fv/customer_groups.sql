create table customer_groups
(
    group_id             bigint auto_increment
        primary key,
    language_id          int               null,
    company_id           int               null,
    user_id              int               null,
    name                 varchar(255)      null,
    percentage_on_price  varchar(255)      null,
    percent_price_amount double            null,
    created_at           datetime          null,
    group_status         tinyint default 1 not null,
    id_kor               bigint  default 1 not null,
    `default`            int(8)            null
);

