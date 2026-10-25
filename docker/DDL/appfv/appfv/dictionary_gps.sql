create table dictionary_gps
(
    id  bigint       not null
        primary key,
    eng varchar(500) null,
    esp varchar(500) null,
    arg varchar(500) null
)
    collate = utf8mb4_unicode_ci;

