<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
| $route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
| $route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/

// Login empresa/admin: URL oficial /login (controlador Auth). Subrutas bajo /login/... para el mismo flujo.
// Portal cliente: /customer/login (Customer_portal::index).
$route['login/selectCompany'] = 'auth/selectCompany';
$route['login/selectApplication'] = 'auth/selectApplication';
$route['login'] = 'auth';
$route['auth'] = 'auth';
// Portal vendedor B2B: URL oficial /sellers (login), /sellers/customer (inicio tras login).
$route['sellers'] = 'seller_portal/index';
$route['sellers/login'] = 'seller_portal/index';
$route['sellers/authenticate'] = 'seller_portal/authenticate';
$route['sellers/logout'] = 'seller_portal/logout';
$route['sellers/customer/new'] = 'seller_portal/customer_new';
$route['sellers/customer/edit/(:num)'] = 'seller_portal/customer_edit/$1';
$route['sellers/customer/view/(:num)'] = 'seller_portal/customer_view/$1';
$route['sellers/customer'] = 'seller_portal/customer';
$route['sellers/catalog'] = 'seller_portal/catalog';
$route['sellers/catalog/pick_customer/(:num)'] = 'seller_portal/catalog_pick_customer/$1';
$route['sellers/catalog/products'] = 'seller_portal/catalog_products';
$route['sellers/catalog/image/(:any)'] = 'seller_portal/catalog_image/$1';
$route['sellers/catalog/cart'] = 'seller_portal/catalog_cart';
$route['sellers/catalog/checkout'] = 'seller_portal/catalog_checkout';
$route['sellers/orders'] = 'seller_portal/orders';
$route['sellers/orders/view/(:num)'] = 'seller_portal/order_view/$1';
$route['signup'] = 'auth/signup';
$route['register'] = 'auth/register';
$route['logout'] = 'auth/logout';

$route['customer'] = 'customer_portal/index';
$route['customer/login'] = 'customer_portal/index';
$route['customer/authenticate'] = 'customer_portal/authenticate';
$route['customer/logout'] = 'customer_portal/logout';
$route['customer/menu'] = 'customer_portal/menu';
$route['customer/home'] = 'customer_portal/catalog';
$route['customer/catalog/embed/cart'] = 'customer_catalog/embed_cart';
$route['customer/catalog/embed'] = 'customer_catalog/embed';
$route['customer/catalog/cart'] = 'customer_portal/catalog_cart';
$route['customer/catalog/checkout'] = 'customer_portal/catalog_checkout';
$route['customer/catalog/order/browse'] = 'customer_portal/catalog_order_browse';
$route['customer/catalog/order/cart'] = 'customer_portal/catalog_order_cart';
$route['customer/catalog/order/checkout'] = 'customer_portal/catalog_order_checkout';
$route['customer/catalog'] = 'customer_portal/catalog';
$route['customer/orders'] = 'customer_portal/orders';
$route['customer/orders/new'] = 'customer_portal/startOrder';

$route['default_controller'] = 'home';
$route['404_override'] = 'admin/notfound';
$route['translate_uri_dashes'] = FALSE;

// Público: confirmar / rechazar orden desde el correo (products/order_confirm/{token})
$route['products/order_confirm/(:any)'] = 'products/order_confirm/$1';
$route['products/order_reject/(:any)'] = 'products/order_reject/$1';

$route['policy'] = 'home/policy';
$route['terms-and-conditions'] = 'home/terms';
$route['copyright'] ='home/copyright';

//ADMIN URL - Plataforma /admin
$route['admin'] = 'admin/admin/index';
$route['admin/'] = 'admin/admin/index';
$route['admin/dashboard'] = 'admin/home/index';
$route['admin/dashboard/(:any)'] = 'admin/home/$1';
// Cierre de sesión del panel admin → /admin/login (no al login unificado /login).
$route['admin/logout'] = 'admin/login/logout';

//COMPANY URL — /company → dashboard empresa (sin esto, la raíz /company no resuelve al panel)
$route['company'] = 'company/dashboard';
$route['company/logout'] = 'auth/logout';
// Recuperar contraseña (login unificado /login → enlace /forgetpassword)
$route['forgetpassword'] = 'company/forgetpassword';
$route['forgetpassword/updatepassword'] = 'company/forgetpassword/updatepassword';
$route['forgetpassword/(:any)'] = 'company/forgetpassword/$1';
$route['reset-password/(:any)'] = 'company/forgetpassword/resetpassword/$1';
#$route['salesman'] = 'SalesmanCatalog';
#$route['marketplace'] = 'marketplace/shop';

//$route['salesman/catalog'] = 'SalesmanCatalog';
$route['company/catalog'] = 'company/catalog/index';
// La ruta /company/catalog/lists también funciona como alias
$route['company/catalog/lists'] = 'company/catalog/index';
$route['company/catalog/formats'] = 'company/catalog/formats';

// Sellerprofile routes — el controlador padre Sellerprofile.php compite con la carpeta sellerprofile/;
// las acciones del subdirectorio requieren rutas explícitas.
$route['company/sellerprofile/lists'] = 'company/sellerprofile/lists';
$route['company/sellerprofile/add'] = 'company/sellerprofile/add';
$route['company/sellerprofile/edit/(:any)'] = 'company/sellerprofile/edit/$1';
$route['company/sellerprofile/create'] = 'company/sellerprofile/create';
$route['company/sellerprofile/update'] = 'company/sellerprofile/update';
$route['company/sellerprofile/delete/(:any)'] = 'company/sellerprofile/delete/$1';
$route['company/sellerprofile/getEditForm/(:any)'] = 'company/sellerprofile/getEditForm/$1';
$route['company/sellerprofile/getAddForm'] = 'company/sellerprofile/getAddForm';
$route['company/sellerprofile/export'] = 'company/sellerprofile/export/index';
$route['company/sellerprofile/csvImport'] = 'company/sellerprofile/csvImport/index';
$route['company/sellerprofile/csvImportSuccess'] = 'company/sellerprofile/csvImportSuccess/index';
$route['company/sellerprofile/listPdf'] = 'company/sellerprofile/listPdf/index';
$route['company/sellerprofile/toggleField/(:any)/(:any)'] = 'company/sellerprofile/toggleField/$1/$2';

// Terms: Terms.php compite con la carpeta terms/; rutas explícitas para herramientas CRUD del subdirectorio.
$route['company/products/changeSku/(:num)'] = 'company/products/changeSku/run/$1';
$route['company/products/inlineTaxonomy/(:num)'] = 'company/products/inlineTaxonomy/run/$1';
$route['company/products/specs/(:num)'] = 'company/products/specs/run/$1';
$route['company/products/specsSave/(:num)'] = 'company/products/specs/save/$1';
$route['company/products/assignAiImages'] = 'company/products/assignAiImages/run';
$route['company/products/inventory/export'] = 'company/products/inventory/export';
$route['company/products/searchProduct'] = 'company/products/searchProduct/index';
$route['company/products/searchProduct/(:any)'] = 'company/products/searchProduct/index';
$route['company/products/searchProductSelect2'] = 'company/products/searchProductSelect2/index';
$route['company/products/searchProductSelect2/(:any)'] = 'company/products/searchProductSelect2/index';
// Linux es case-sensitive: importimages ≠ ImportImages.php
$route['company/products/importimages'] = 'company/products/ImportImages/index';
$route['company/products/importimages/(:any)'] = 'company/products/ImportImages/$1';
$route['company/categories/importimages'] = 'company/categories/ImportImages/index';
$route['company/categories/importimages/(:any)'] = 'company/categories/ImportImages/$1';
$route['company/categories/export'] = 'company/categories/export/index';
$route['company/categories/groups'] = 'company/categories/groups/run';
$route['company/categories/groups/saveGroup'] = 'company/categories/groups/saveGroup';
$route['company/categories/groups/saveSubgroup'] = 'company/categories/groups/saveSubgroup';
$route['company/categories/groups/updateGroupImage/(:num)'] = 'company/categories/groups/updateGroupImage/$1';
$route['company/categories/groups/updateSubgroupImage/(:num)'] = 'company/categories/groups/updateSubgroupImage/$1';
$route['company/categories/groups/deleteGroup/(:num)'] = 'company/categories/groups/deleteGroup/$1';
$route['company/categories/groups/deleteSubgroup/(:num)'] = 'company/categories/groups/deleteSubgroup/$1';
$route['company/categories/groups/tree'] = 'company/categories/groups/tree';
$route['company/categories/groups/(:num)'] = 'company/categories/groups/run/$1';
$route['company/customers/CSVExport'] = 'company/customers/CSVExport/index';
$route['company/import_template/download/(:any)'] = 'company/import_template/download/$1';
$route['company/currencies/csvImport'] = 'company/currencies/csvImport/index';
$route['company/currencies/csvImportSuccess'] = 'company/currencies/csvImportSuccess/index';
$route['company/currencies/export'] = 'company/currencies/export/index';
$route['company/currencies/listPdf'] = 'company/currencies/listPdf/index';
$route['company/units/csvImport'] = 'company/units/csvImport/index';
$route['company/units/csvImportSuccess'] = 'company/units/csvImportSuccess/index';
$route['company/groups/csvImport'] = 'company/groups/csvImport/index';
$route['company/groups/csvImportSuccess'] = 'company/groups/csvImportSuccess/index';
$route['company/groups/export'] = 'company/groups/export/index';
$route['company/groups/listPdf'] = 'company/groups/listPdf/index';
$route['company/users/csvImport'] = 'company/users/csvImport/index';
$route['company/users/csvImportSuccess'] = 'company/users/csvImportSuccess/index';
$route['company/users/export'] = 'company/users/export/index';
$route['company/users/listPdf'] = 'company/users/listPdf/index';
$route['company/categories/csvImport'] = 'company/categories/csvImport/index';
$route['company/categories/CSVImport'] = 'company/categories/csvImport/index';
$route['company/categories/CSVImportSuccess/run'] = 'company/categories/CSVImportSuccess/run';
$route['company/categories/csvImportSuccess/run'] = 'company/categories/CSVImportSuccess/run';
$route['company/terms/lists'] = 'company/Terms/lists';
$route['company/terms/export'] = 'company/terms/export/index';
$route['company/terms/csvImport'] = 'company/terms/csvImport/index';
$route['company/terms/csvImportSuccess'] = 'company/terms/csvImportSuccess/index';
$route['company/warehouses/csvImportSuccess'] = 'company/warehouses/csvImportSuccess/run';
$route['company/customers/csvImportSuccess'] = 'company/customers/csvImportSuccess/run';
$route['company/terms/listPdf'] = 'company/terms/listPdf/index';

// Activar/desactivar: forzar Terms.php (como /lists), no el subdirectorio terms/Disable.
$route['company/terms/setDefault/(:any)'] = 'company/terms/setDefault/run/$1';
$route['company/terms/enable/(:num)'] = 'company/Terms/enable/$1';
$route['company/terms/disable/(:num)'] = 'company/Terms/disable/$1';
$route['company/units/setDefault/(:any)'] = 'company/units/setDefault/run/$1';
$route['company/groups/setDefault/(:any)'] = 'company/groups/setDefault/run/$1';
// Activar/desactivar: Home del módulo groups (misma clase que el listado CRUD).
$route['company/groups/enable/(:any)'] = 'company/groups/home/enable/$1';
$route['company/groups/disable/(:any)'] = 'company/groups/home/disable/$1';

// Countries routes
$route['company/countries/updatePhonemask/(:any)'] = 'company/countries/updatePhonemask/run/$1';
$route['company/countries/lists'] = 'company/countries/lists/index';

// States/Cities por país/estado (cascada)
$route['company/states/searchstate'] = 'company/states/SearchState/index';
$route['company/states/searchState'] = 'company/states/SearchState/index';
$route['company/cities/searchcity'] = 'company/cities/SearchCity/index';
$route['company/cities/searchCity'] = 'company/cities/SearchCity/index';
$route['company/postal/searchzip'] = 'company/postal/SearchZip/index';
$route['company/postal/searchZip'] = 'company/postal/SearchZip/index';

// Órdenes: duplicación silenciosa (mismo cliente, nuevo número)
$route['company/orders/duplicate_silent'] = 'company/orders/DuplicateSilent/index';
// Órdenes: verificar contraseña del usuario antes de eliminar
$route['company/orders/verify_delete_password'] = 'company/orders/VerifyDeletePassword/index';
// Órdenes: vista previa para modal de confirmación de eliminación
$route['company/orders/delete_preview'] = 'company/orders/DeletePreview/index';
// Órdenes: eliminación masiva (API JSON, sin redirección)
$route['company/orders/bulk_delete'] = 'company/orders/BulkDelete/index';
// Órdenes: vista previa para combinación (validación y datos del modal)
$route['company/orders/combine_preview'] = 'company/orders/CombinePreview/index';
// Órdenes: ejecutar combinación de órdenes
$route['company/orders/combine_orders'] = 'company/orders/CombineOrders/index';
$route['company/orders/importPreview'] = 'company/orders/ImportPreview/index';
$route['company/orders/import_preview'] = 'company/orders/ImportPreview/index';
$route['company/orders/importJsonPreview'] = 'company/orders/ImportJson/preview';
$route['company/orders/importJsonConfirm'] = 'company/orders/ImportJson/confirm';
$route['company/orders/importJsonSample'] = 'company/orders/ImportJson/sample';
$route['company/orders/syncOpenCart'] = 'company/orders/SyncOpenCart/index';
$route['company/orders/syncopencart'] = 'company/orders/SyncOpenCart/index';

// Inventory: URL menú inventory/orders → controlador real inventory/Order::index
$route['company/inventory/orders'] = 'company/inventory/order/index';
$route['company/inventory/order'] = 'company/inventory/order/index';
// Alias por error frecuente de tipeo en URL
$route['company/inventary/order'] = 'company/inventory/order/index';