create table tb_inventory
(
    company_id     bigint        not null,
    product_id     bigint        not null,
    sku            varchar(255)  null,
    name           varchar(255)  null,
    descriptions   text          null,
    qty            float         null,
    modify_date    datetime      null,
    sp             float         null,
    pp             float         null,
    sale_price     double(16, 2) not null,
    purchase_price double(8, 2)  not null,
    category_id    varchar(255)  null comment 'PK of categories'
)
    engine = MyISAM;

