create table vendors
(
    user_id            bigint unsigned auto_increment
        primary key,
    unique_id          varchar(255)                             null,
    company_id         bigint                                   null,
    first_name         varchar(255) default ''                  null,
    last_name          varchar(255) default ''                  null,
    username           varchar(255) default ''                  null,
    email              varchar(255) default ''                  null,
    password           varchar(255) default ''                  null,
    profile            int                                      null,
    phone_number       varchar(255) default ''                  null,
    cell_number        varchar(255) default ''                  null,
    fax                varchar(255) default ''                  null,
    profile_image      varchar(255) default ''                  null,
    email_verification tinyint(3)   default 0                   null comment '0=Not Verified 1= Verified
',
    otp                varchar(255) default ''                  null,
    created            datetime     default current_timestamp() null,
    updated_at         timestamp    default current_timestamp() null on update current_timestamp(),
    status             smallint     default 1                   null comment '0=InActive 1= Active
',
    id_kor             bigint       default 0                   null,
    `default`          int                                      null,
    token              text                                     null,
    add_customer       int                                      not null,
    update_customer    int                                      not null,
    send_catalog       int                                      not null,
    all_customers      int(5)       default 0                   null,
    proforma           int          default 0                   not null
)
    charset = latin1;

