create table cities
(
    id       int auto_increment
        primary key,
    name     varchar(30) not null,
    state_id int         not null
)
    charset = latin1;

