create table product_images
(
    img_id       bigint auto_increment
        primary key,
    product_id   int                                    null comment 'PK of Products',
    language_id  int        default 1                   null,
    images       varchar(255)                           null,
    created      datetime                               null,
    updated      datetime   default current_timestamp() not null on update current_timestamp(),
    Img          longblob                               null,
    sImgURL      longtext                               null,
    ImgURL       longtext                               null,
    id_kor       bigint     default 0                   null,
    Description  longtext                               null,
    company_id   bigint                                 null,
    sku          varchar(255)                           null,
    img_default  bit                                    null,
    img_order    bigint     default 1                   null,
    imageBlob    blob                                   null,
    image_status tinyint(1) default 1                   null,
    comprimed    int        default 0                   null
);

