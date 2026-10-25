create table status_cart
(
    id_status        int(10) auto_increment
        primary key,
    name_spa         varchar(50)      null,
    name_eng         varchar(50)      null,
    color            varchar(50)      null,
    cod              varchar(50)      null,
    class            varchar(50)      null,
    selected_default varchar(50)      null,
    allow_toremove   int(5) default 0 null,
    multiple_cart    int(5)           null,
    nosubmit_order   int              null
)
    charset = latin1;

