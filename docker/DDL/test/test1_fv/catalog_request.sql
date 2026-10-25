create table catalog_request
(
    request_id   bigint auto_increment
        primary key,
    user_id      int                                    not null comment 'Pk of vendors',
    customer_id  int                                    not null comment 'PK of customers',
    descriptions tinytext                               null,
    created      datetime   default current_timestamp() null,
    updated      timestamp  default current_timestamp() not null on update current_timestamp(),
    status       tinyint(3) default 1                   not null
);

