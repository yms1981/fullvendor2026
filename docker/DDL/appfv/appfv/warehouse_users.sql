create table warehouse_users
(
    user_id            bigint unsigned auto_increment
        primary key,
    unique_id          varchar(255) default '' null,
    company_id         bigint                  null,
    first_name         varchar(255) default '' null,
    last_name          varchar(255) default '' null,
    username           varchar(255) default '' null,
    email              varchar(255) default '' null,
    password           varchar(255) default '' null,
    phone_number       varchar(255) default '' null,
    cell_number        varchar(255) default '' null,
    fax                varchar(255) default '' null,
    profile_image      varchar(255) default '' null,
    email_verification tinyint(3)   default 0  null comment '0=Not Verified 1= Verified
',
    otp                varchar(255) default '' null,
    created            datetime                null,
    status             smallint     default 1  null comment '0=InActive 1= Active
',
    token              text                    null,
    add_customer       int          default 0  null,
    update_customer    int          default 0  null,
    send_catalog       int          default 0  null,
    deleted_at         datetime                not null
)
    charset = latin1;

