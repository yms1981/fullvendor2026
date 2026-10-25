create table import_history
(
    import_id        bigint auto_increment
        primary key,
    company_id       int          not null,
    import_date      datetime     null,
    importedfile     varchar(255) not null,
    records_imported int          not null
);

