create table product_images
(
    img_id       bigint auto_increment
        primary key,
    product_id   int                                  null comment 'PK of Products',
    language_id  int      default 1                   not null,
    images       varchar(255)                         null,
    created      datetime                             null,
    updated      datetime default current_timestamp() not null on update current_timestamp(),
    Img          longblob                             not null,
    sImgURL      longtext                             not null,
    ImgURL       longtext                             not null,
    id_kor       bigint   default 0                   not null,
    Description  longtext                             null,
    company_id   bigint                               null,
    sku          varchar(255)                         null,
    img_default  bit                                  null,
    img_order    bigint                               null,
    imageBlob    blob                                 null,
    image_status bit                                  null,
    comprimed    int      default 0                   not null
);

