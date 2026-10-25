create table banner
(
    bannerId          bigint auto_increment
        primary key,
    bannerHeading     varchar(255) null,
    bannerDescription text         null,
    bannerImage       varchar(255) null
)
    engine = MyISAM
    charset = latin1;

