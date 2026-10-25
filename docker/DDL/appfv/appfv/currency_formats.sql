create table currency_formats
(
    CurrencyName   varchar(100) not null,
    CurrencyCode   varchar(100) not null,
    CurrencyFormat varchar(100) not null,
    currOutput     varchar(100) null,
    CodeRegional   varchar(100) null,
    format_id      int auto_increment
        primary key,
    `order`        int          null
)
    charset = latin1;

