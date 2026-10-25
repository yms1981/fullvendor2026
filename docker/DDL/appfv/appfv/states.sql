create table states
(
    id         int auto_increment
        primary key,
    name       varchar(30)   not null,
    country_id int default 1 not null,
    created_on datetime      null
)
    charset = latin1;

