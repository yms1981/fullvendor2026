create table logs
(
    log_id     bigint auto_increment
        primary key,
    user_id    int          not null comment 'PK of vendors',
    company_id int          not null,
    log_type   varchar(200) not null comment 'all,sync,login',
    notes      text         null,
    created_on datetime     null
);

