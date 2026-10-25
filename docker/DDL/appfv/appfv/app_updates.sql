create table appfv.app_updates
(
    fecha        datetime null,
    company_id   bigint   not null,
    updatenumber bigint   not null,
    ID           bigint
)
    engine = InnoDB
    collate = utf8mb4_unicode_ci;

create index ID
    on appfv.app_updates (ID);

alter table appfv.app_updates
    modify ID bigint auto_increment;

