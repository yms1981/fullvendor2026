create table cart
(
    cart_id       bigint auto_increment
        primary key,
    user_id       bigint                                 not null comment 'Pk of vendors',
    language_id   tinyint(3)                             not null comment 'PK of languages',
    product_id    bigint                                 not null comment 'PK of products',
    qty           int                                    not null,
    discount      float                                  not null,
    discount_type tinyint(3) default 1                   not null comment '1 = in %, 2 = in $',
    comments      text                                   null,
    created       datetime                               null,
    updated       timestamp  default current_timestamp() not null on update current_timestamp(),
    deleted       int        default 0                   null,
    customer_id   bigint                                 null
);

