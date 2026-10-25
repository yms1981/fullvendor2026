create table cms
(
    pageId    bigint auto_increment
        primary key,
    pageTitle varchar(100)                          null,
    pageText  tinytext                              null,
    link      varchar(255)                          null,
    content   longtext                              null,
    created   timestamp                             null,
    modified  timestamp default current_timestamp() null on update current_timestamp()
)
    charset = latin1;

