create table Valid_Img
(
    pro_id      bigint       null,
    img_id      bigint       not null,
    product_id  int          null comment 'PK of Products',
    language_id int          not null,
    images      varchar(255) null,
    created     datetime     null,
    updated     datetime     not null,
    Img         longblob     not null,
    sImgURL     longtext     not null,
    ImgURL      longtext     not null,
    id_kor      bigint       not null,
    Description longtext     null,
    company_id  bigint       null,
    sku         varchar(255) null,
    img_default bit          null
)
    engine = MyISAM;

