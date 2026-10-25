create table catalog
(
    catalog_id      bigint auto_increment
        primary key,
    catalog_name    varchar(255)  not null,
    company_id      int           not null,
    cat_id          text          not null comment 'pk of categories',
    product_name    text          null,
    status          int           not null,
    created         datetime      null,
    customer_emails longtext      null,
    email_sent_on   datetime      null,
    attach          varchar(50)   null,
    dayexpired      int default 0 not null,
    default_catalog int           null,
    cabeza_pagina   varchar(255)  null,
    deleted_at      datetime      null
);

