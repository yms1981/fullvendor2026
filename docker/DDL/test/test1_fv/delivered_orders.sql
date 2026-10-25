create table delivered_orders
(
    delivered_id       bigint auto_increment
        primary key,
    order_id           bigint                                not null comment 'PK of orders',
    product_id         bigint                                not null comment 'PK of products',
    delivered_quantity float                                 null,
    delivered_date     timestamp default current_timestamp() not null on update current_timestamp()
);

