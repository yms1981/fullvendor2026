create table currencybycountries
(
    id         bigint auto_increment
        primary key,
    GroupID    varchar(300) null,
    Name       varchar(300) null,
    Contry     longtext     null,
    Code       varchar(50)  null,
    Symbol     varchar(50)  null,
    GroupIdspa varchar(255) null,
    Namespa    varchar(255) null,
    `default`  varchar(50)  null
)
    collate = utf8mb4_unicode_ci;

create fulltext index GroupID_Name
    on currencybycountries (GroupID, Name);

