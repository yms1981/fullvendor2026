create table format_numeric
(
    format_id      int auto_increment
        primary key,
    numeric_format varchar(50) null,
    radixPoint     varchar(11) null,
    groupSeparator varchar(11) null,
    digits         int         null,
    `default`      int         null,
    code_regional  varchar(50) null
)
    charset = latin1;

