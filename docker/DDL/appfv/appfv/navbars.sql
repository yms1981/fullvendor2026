create table navbars
(
    navbar_id        int auto_increment
        primary key,
    url_nav          varchar(150)                         null,
    identify         varchar(255)                         null,
    name_eng         varchar(150)                         null,
    name_spa         varchar(255)                         null,
    created_at       datetime default current_timestamp() null on update current_timestamp(),
    updated_at       datetime default current_timestamp() null on update current_timestamp(),
    father_id        int                                  null,
    icon             varchar(150)                         null,
    `order`          int                                  null,
    config           int                                  null,
    is_active_to_rol int      default 1                   null
)
    charset = latin1;

