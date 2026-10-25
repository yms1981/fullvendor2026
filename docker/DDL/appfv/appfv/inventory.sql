create table inventory
(
    company_id      bigint                               not null,
    product_id      bigint                               not null,
    kor_id          bigint   default 0                   null,
    qty             float    default 0                   null,
    sale_price      float    default 0                   null,
    purchased_price float    default 0                   null,
    modify_date     datetime default current_timestamp() null on update current_timestamp(),
    primary key (company_id, product_id)
)
    charset = latin1;

