create table currency
(
    currency_id int auto_increment
        primary key,
    country     varchar(100) null,
    currency    varchar(100) null,
    code        varchar(100) null,
    symbol      varchar(100) null,
    company_id  int          not null,
    status      int          not null,
    created     datetime     null
);

