create table detail_car
(
    detail_id   int auto_increment
        primary key,
    product_id  int                        null,
    token_car   varchar(255)               null,
    customer_id int                        null,
    company_id  int                        null,
    qty         int(8)                     null,
    created     datetime                   null,
    sale_price  double(16, 2) default 0.00 null,
    comment     varchar(255)               null,
    cart_id     int                        null,
    session_id  int                        null,
    cookie_id   int                        null
)
    charset = latin1;

