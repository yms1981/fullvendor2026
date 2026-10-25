create table catalog_cart
(
    cart_id             bigint auto_increment
        primary key,
    customer_id         bigint                                null comment 'Pk of customers',
    product_id          bigint                                null comment 'PK of products',
    qty                 int                                   null,
    created             datetime                              null,
    updated             timestamp default current_timestamp() null on update current_timestamp(),
    token_car           varchar(255)                          null,
    user_id             int                                   null,
    company_id          int                                   null,
    catalog_id          int(8)                                null,
    expired_date        datetime                              null,
    token_kor           varchar(255)                          null,
    section_address     int                                   null,
    show_logofullvendor int                                   null,
    return_admin        int                                   null,
    order_sent          int                                   null,
    catalog_text        varchar(255)                          null,
    view                int(10)   default 0                   null,
    status_cart         int(10)                               null,
    login_seller        int(10)                               null,
    disable_total       int(10)                               null,
    type_cart           varchar(50)                           null,
    deleted_at          datetime                              null,
    father_id           int                                   null,
    cart_number         varchar(50)                           null,
    order_id            int                                   null
);

