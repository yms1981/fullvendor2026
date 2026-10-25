create table catalog_sent_mail
(
    catalog_sent_id    bigint auto_increment
        primary key,
    company_id         int           not null,
    catlog_reqest_id   int           not null comment 'PK of catalog_request',
    hide_price         int           not null comment '0=No,1=Yes',
    catalog_id         int           not null comment 'PK of catalog',
    catalog_text       varchar(1000) null,
    catalog_send_type  varchar(255)  null,
    catalog_email_text text          null,
    send_date          datetime      null,
    customer_emails    longtext      null,
    customerIds        longtext      null,
    expiration         datetime      null on update current_timestamp()
);

