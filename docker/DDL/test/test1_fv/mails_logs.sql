create table mails_logs
(
    id        bigint auto_increment
        primary key,
    customers longtext not null,
    subject   longtext not null,
    content   longtext not null,
    fecha     datetime not null
)
    charset = latin1;

