create table order_status_history
(
    history_id int auto_increment
        primary key,
    order_id   int                                  null,
    company_id int                                  null,
    user_id    int                                  null,
    cod_status int                                  null,
    created_at datetime default current_timestamp() null,
    updated_at datetime default current_timestamp() null on update current_timestamp()
)
    collate = utf8mb4_unicode_ci;

