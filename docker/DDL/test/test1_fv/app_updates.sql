create table app_updates
(
    fecha        datetime null,
    company_id   bigint   not null,
    updatenumber bigint   not null,
    ID           bigint
)
    collate = utf8mb4_unicode_ci;

create index ID
    on app_updates (ID);

alter table app_updates
    modify ID bigint auto_increment;

