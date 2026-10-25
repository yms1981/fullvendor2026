create table format_date
(
    id              int auto_increment
        primary key,
    format_php      varchar(50) null,
    format_js       varchar(50) null,
    language_id     int         null,
    date_firstmonth varchar(50) null,
    `default`       varchar(50) null,
    month_year      varchar(50) null
)
    collate = utf8mb4_unicode_ci;

