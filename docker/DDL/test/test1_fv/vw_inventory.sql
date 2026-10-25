create table vw_inventory
(
    company_id       bigint        null,
    product_id       bigint        not null comment 'PK of products',
    sku              varchar(255)  null,
    name             varchar(255)  null,
    Inventory        double(19, 2) null,
    sale_price       double(20, 6) null,
    amount_inventory double(19, 2) null
)
    engine = MyISAM;

