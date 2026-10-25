create table user_profiles
(
    profile_id           bigint unsigned auto_increment
        primary key,
    profile_name         varchar(255)         null,
    company_id           bigint               null,
    order_discount       tinyint(3) default 0 not null comment '0=No 1=Yes',
    order_disc_price     varchar(255)         null,
    order_net_discount   tinyint(3) default 0 not null comment '0=No 1=Yes',
    order_net_disc_price varchar(255)         null,
    can_change_price     tinyint(3) default 0 not null comment '0=No 1=Yes',
    can_send_catalog     tinyint(3) default 0 not null comment '0=No 1=Yes',
    can_create_customer  tinyint(3) default 0 not null comment '0=No 1=Yes',
    created              datetime             null,
    status               smallint   default 1 null comment '0=InActive 1= Active
',
    `default`            int(5)               null,
    deleted_at           datetime             null
)
    charset = latin1;

