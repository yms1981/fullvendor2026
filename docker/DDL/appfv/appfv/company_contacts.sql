create table company_contacts
(
    c_id       bigint auto_increment
        primary key,
    company_id bigint       null,
    name       varchar(255) null,
    email      varchar(255) null,
    website    varchar(255) null,
    phone      varchar(255) null,
    cell_phone varchar(255) null
);

