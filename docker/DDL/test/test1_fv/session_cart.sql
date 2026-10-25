create table session_cart
(
    id             int auto_increment
        primary key,
    company_id     int                                       null,
    customer_id    int                                       null,
    total_amount   double(19, 2) default 0.00                null,
    sub_total      double(19, 2) default 0.00                null,
    discount_total double(19, 2) default 0.00                null,
    qty_products   int           default 0                   null,
    created_at     datetime      default current_timestamp() null,
    update_at      timestamp     default current_timestamp() null on update current_timestamp(),
    session_token  varchar(50)                               null,
    is_closed      int           default 0                   null
);

