-- Estados de carrito Open Cart (referencia fija).
-- Sin estos registros el listado no muestra ESTADO ni ACCIONES.
INSERT IGNORE INTO `status_cart` (`id_status`, `name_spa`, `name_eng`, `color`, `cod`, `class`, `selected_default`, `allow_toremove`, `multiple_cart`, `nosubmit_order`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1,'Enviado','Sent','#1EA910','1','text-success','1',1,0,0,NULL,NULL,NULL),
(2,'Visto','Viewed','#3498DB','2','text-info',NULL,1,0,0,NULL,NULL,NULL),
(3,'Abierto','Open','#F39C12','3','text-warning',NULL,1,0,0,NULL,NULL,NULL),
(4,'Completado','Completed','#CB4335','4','text-danger',NULL,0,1,1,NULL,NULL,NULL);
