create table versiones
(
    id         bigint   null,
    company_id bigint   not null,
    fecha      datetime null,
    version    bigint   not null
)
    engine = MyISAM;

