create table admin
(
    userId   bigint auto_increment
        primary key,
    name     varchar(255)                          null,
    username varchar(500)                          null,
    email    varchar(500)                          null,
    password varchar(1000)                         null,
    created  timestamp                             null,
    edited   timestamp default current_timestamp() not null on update current_timestamp(),
    status   smallint  default 1                   null comment '1(Active), 0(Deactive)'
)
    charset = latin1;

