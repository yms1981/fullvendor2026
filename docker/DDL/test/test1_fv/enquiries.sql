create table enquiries
(
    enquiryId bigint auto_increment
        primary key,
    name      varchar(500)                          null,
    email     varchar(500)                          null,
    phone     varchar(100)                          null,
    subject   varchar(255)                          null,
    message   text                                  null,
    created   timestamp default current_timestamp() null,
    status    smallint  default 0                   null
)
    charset = latin1;

