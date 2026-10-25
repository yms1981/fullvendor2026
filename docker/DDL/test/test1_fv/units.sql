create table units
(
    unit        varchar(50)   not null,
    description varchar(500)  not null,
    company_id  int           not null,
    status      int           null,
    created     date          null,
    unit_id     int(20),
    `default`   int default 0 null,
    decimales   int           null,
    formato     varchar(50)   null,
    id_kor      bigint        null,
    primary key (unit, company_id)
)
    comment '	' charset = latin1;

create index unit_id
    on units (unit_id);

alter table units
    modify unit_id int(20) auto_increment;

