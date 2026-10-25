create table newsletter
(
    newsletterId bigint auto_increment
        primary key,
    email        varchar(500)      null,
    created      timestamp         null,
    status       tinyint default 1 null comment '1-Subscribed, 0-Unsubscribed'
)
    engine = MyISAM
    charset = latin1;

