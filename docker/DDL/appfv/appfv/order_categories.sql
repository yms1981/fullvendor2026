create table order_categories
(
    order_category_id int auto_increment
        primary key,
    category_id       int                                   null,
    catalog_id        int                                   null,
    company_id        int                                   null,
    `order`           int                                   null,
    created_at        timestamp default current_timestamp() null
)
    charset = latin1;

