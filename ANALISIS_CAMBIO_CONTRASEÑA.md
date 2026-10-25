# Análisis Completo del Flujo de Cambio de Contraseña

## 📋 Resumen Ejecutivo

El sistema de cambio de contraseña utiliza un flujo AJAX que valida datos en el cliente y servidor, actualiza la contraseña en la API (tanto para `companies` como para `users`), y luego cierra la sesión del usuario redirigiendo al login.

---

## 🔄 Flujo Completo Paso a Paso

### **FASE 1: Interfaz de Usuario (Frontend)**

#### 1.1. Vista del Formulario
**Archivo:** `mvc/application/views/company/change_password.php`

- **Campos del formulario:**
  - `c_password`: Contraseña actual (requerido)
  - `n_password_confirmation`: Nueva contraseña (requerido)
  - `n_password`: Confirmación de nueva contraseña (requerido)

- **Formulario:**
  ```html
  <form id="changePasswordForm" method="post" action="<?= company_url('change_password/update') ?>">
  ```

#### 1.2. Interceptación del Submit (JavaScript)
**Líneas 46-137 de `change_password.php`**

**Proceso:**
1. El evento `submit` es interceptado con `e.preventDefault()`
2. Se serializan los datos del formulario con `form.serialize()`
3. El botón de submit se deshabilita y muestra "Processing..."
4. Se envía una petición AJAX con:
   - `url`: `company/change_password/update`
   - `type`: `POST`
   - `dataType`: `json`
   - `headers`: `X-Requested-With: XMLHttpRequest` (para identificar petición AJAX)

---

### **FASE 2: Controlador - Método `update()`**

**Archivo:** `mvc/application/controllers/company/Change_password.php`
**Método:** `update()` (líneas 115-394)

#### 2.1. Validación Inicial
```php
// Verificar que sea POST
if ($this->input->method() !== 'post') {
    redirect(company_url('change_password'), 'refresh');
    return;
}
```

#### 2.2. Obtención del Company ID
```php
$compId = $this->getCompany();
```
- Si no hay `compId`, devuelve error JSON (si es AJAX) o redirige con flash message

#### 2.3. Lectura de Datos POST
```php
$c_password = $this->input->post('c_password');
$n_password_confirmation = $this->input->post('n_password_confirmation'); // Nueva contraseña
$n_password = $this->input->post('n_password'); // Confirmación
```

#### 2.4. Validaciones del Servidor

**a) Campos Vacíos:**
```php
if (empty($c_password) || empty($n_password_confirmation) || empty($n_password)) {
    // Retorna JSON error o flash message
}
```

**b) Coincidencia de Contraseñas:**
```php
if ($n_password_confirmation !== $n_password) {
    // Retorna JSON error o flash message
}
```

**c) Obtener Company de la Base de Datos:**
```php
$company = $this->mymodel->get('companies', true, 'company_id', $compId);
```

**d) Verificar Contraseña Actual:**
```php
if (!password_verify($c_password, $company->password)) {
    // Retorna error: "The current password is incorrect"
}
```

**e) Validar que Nueva Contraseña sea Diferente:**
```php
if (password_verify($new_password, $company->password)) {
    // Retorna error: "The new password must be different from the current password"
}
```

#### 2.5. Hash de la Nueva Contraseña
```php
$hashedPassword = $this->enc_password($new_password);
```
**Método `enc_password()` (MY_Controller.php:190-194):**
```php
public function enc_password($password) {
    $encrypted_password = password_hash($password, PASSWORD_DEFAULT);
    return $encrypted_password;
}
```
- Utiliza `password_hash()` con algoritmo `PASSWORD_DEFAULT` (bcrypt)

---

### **FASE 3: Actualización en la API**

#### 3.1. Actualización de Company
**Líneas 294-318 de `Change_password.php`**

```php
$companyUpdateData = ['password' => $hashedPassword];
$companyResponse = $this->put("company/{$compId}", $companyUpdateData);
```

**Flujo del método `put()`:**
1. **ApiClient::put()** (línea 294-300):
   ```php
   function put($resource, $params = [], $associativeResponse=true) {
       $options = ['json' => $params];
       return $this->apiRequest($resource, 'PUT', $options, $associativeResponse);
   }
   ```

2. **ApiClient::apiRequest()** (línea 113-173):
   - Obtiene el token de usuario: `$daToken = $this->getUserToken()`
   - Configura headers:
     ```php
     $options['headers'] = [
         'Accept' => 'application/json',
         'Authorization' => 'Bearer ' . $daToken
     ];
     ```
   - Realiza la petición HTTP PUT con Guzzle:
     ```php
     $response = $this->getHttpClient()->request(
         'PUT',
         "http://{API_SERVER}/api/v2/company/{$compId}",
         $options
     );
     ```
   - URL completa: `http://{API_SERVER}/api/v2/company/{company_id}`
   - Body JSON: `{"password": "{hashed_password}"}`

3. **Verificación de Errores:**
   ```php
   if ($this->hasError($companyResponse)) {
       // Retorna error JSON o flash message
   }
   ```
   - `hasError()` verifica si la respuesta contiene `['error']` o `->error`

#### 3.2. Actualización de User (Opcional)
**Líneas 320-338 de `Change_password.php`**

```php
$user = $this->mymodel->get_by('users', true, ['email' => $company->email]);
if ($user) {
    $userUpdateData = ['password' => $hashedPassword];
    $userResponse = $this->put("user/{$user->id}", $userUpdateData);
    // Si falla, solo se registra en logs (no falla el proceso completo)
}
```

**Nota importante:** Si la actualización del usuario falla, solo se registra en logs. El proceso continúa porque la contraseña de la compañía ya se actualizó.

---

### **FASE 4: Respuesta y Cierre de Sesión**

#### 4.1. Respuesta AJAX (Éxito)
**Líneas 340-358 de `Change_password.php`**

```php
if ($this->input->is_ajax_request()) {
    $msg = '["Password changed successfully. Please login again.", "success", "#A5DC86"]';
    $this->flashMessage($msg);
    $this->doLogOut();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Password changed successfully. You will be redirected to login.',
        'redirect' => base_url('login')
    ]);
    return;
}
```

#### 4.2. Cierre de Sesión
**Método `doLogOut()` (HasAuth.php:56-64):**

```php
protected function doLogOut() {
    $this->removeUserData('userId');
    $this->removeUserData('admin');
    $this->removeUserData('user');
    $this->removeUserData('company');
    $this->removeUserData('compId');
    $this->removeUserData('application');
    $this->session->sess_destroy();
}
```

#### 4.3. Respuesta No-AJAX (Fallback)
**Líneas 360-371 de `Change_password.php`**

```php
$msg = '["Password changed successfully. Please login again.", "success", "#A5DC86"]';
$this->flashMessage($msg);
$this->doLogOut();
header('Location: ' . base_url('login'));
exit;
```

---

### **FASE 5: Manejo de Respuesta en Frontend**

#### 5.1. Respuesta Exitosa
**Líneas 68-87 de `change_password.php` (JavaScript)**

```javascript
if (response && response.success) {
    swal({
        title: "Success!",
        text: response.message || "Password changed successfully...",
        icon: "success",
        button: { text: "OK", ... }
    }).then(function() {
        window.location.href = response.redirect || '<?= base_url('login') ?>';
    });
}
```

#### 5.2. Respuesta de Error
**Líneas 88-103 de `change_password.php` (JavaScript)**

```javascript
else {
    const errorMsg = (response && response.message) ? response.message : 'Failed to change password';
    swal({
        title: "Error!",
        text: errorMsg,
        icon: "error",
        ...
    });
    submitButton.prop('disabled', false).html(originalText); // Reactivar botón
}
```

#### 5.3. Error de Red/Servidor
**Líneas 105-133 de `change_password.php` (JavaScript)**

- Si la respuesta es HTML (redirección del servidor), recarga la página para mostrar el flash message
- Si es un error de red, muestra alerta SweetAlert con el mensaje de error

---

## 🔍 Puntos Críticos del Flujo

### ✅ Validaciones Implementadas

1. **Frontend (HTML5):**
   - Campos `required` en los inputs
   - Validación de coincidencia de contraseñas (si está implementada en JavaScript)

2. **Backend:**
   - Verificación de método POST
   - Validación de campos no vacíos
   - Validación de coincidencia de contraseñas
   - Verificación de contraseña actual con `password_verify()`
   - Validación de que nueva contraseña sea diferente

3. **API:**
   - Autenticación mediante Bearer Token
   - Validaciones del lado del servidor (Laravel)

### ⚠️ Posibles Problemas

1. **Actualización de User puede fallar silenciosamente:**
   - Si la actualización del usuario falla, solo se registra en logs
   - La contraseña de la compañía ya se actualizó, pero el usuario podría tener problemas para iniciar sesión

2. **Doble actualización:**
   - Se actualiza tanto `companies` como `users` con la misma contraseña hasheada
   - Si hay inconsistencia entre ambas tablas, podría haber problemas

3. **Manejo de errores de API:**
   - Si la API devuelve un error, se muestra el mensaje pero el proceso se detiene
   - No hay rollback si la actualización de company fue exitosa pero la de user falla

### 🔐 Seguridad

1. **Hash de contraseña:**
   - Utiliza `password_hash()` con `PASSWORD_DEFAULT` (bcrypt)
   - La contraseña nunca se envía en texto plano a la API

2. **Autenticación:**
   - Todas las peticiones a la API requieren Bearer Token
   - El token se obtiene de la sesión del usuario

3. **Cierre de sesión:**
   - Después de cambiar la contraseña, se destruye la sesión
   - El usuario debe iniciar sesión nuevamente con la nueva contraseña

---

## 📊 Diagrama de Flujo Simplificado

```
[Usuario llena formulario]
         ↓
[Click en Submit]
         ↓
[JavaScript intercepta submit]
         ↓
[Petición AJAX POST a /company/change_password/update]
         ↓
[Controlador valida datos]
         ↓
[Verifica contraseña actual con password_verify()]
         ↓
[Hashea nueva contraseña con password_hash()]
         ↓
[PUT a API: /api/v2/company/{id}]
         ↓
[PUT a API: /api/v2/user/{id} (si existe)]
         ↓
[Guarda flash message de éxito]
         ↓
[Cierra sesión (doLogOut)]
         ↓
[Retorna JSON con success: true]
         ↓
[JavaScript muestra alerta SweetAlert]
         ↓
[Usuario hace click en OK]
         ↓
[Redirige a /login]
```

---

## 🛠️ Archivos Involucrados

1. **Vista:**
   - `mvc/application/views/company/change_password.php`

2. **Controlador:**
   - `mvc/application/controllers/company/Change_password.php`

3. **Core Classes:**
   - `mvc/application/core/ApiClient.php` (métodos `put()`, `apiRequest()`)
   - `mvc/application/core/MY_Controller.php` (método `enc_password()`)
   - `mvc/application/core/HasAuth.php` (método `doLogOut()`)
   - `mvc/application/core/Company_Controller.php` (hereda de Application_Controller)

4. **API (Laravel):**
   - `api/app/Http/Controllers/CompanyController.php` (método `update()`)
   - `api/app/Http/Controllers/UserController.php` (método `update()`)

---

## 📝 Notas Adicionales

1. **Logging:**
   - El controlador tiene logging extensivo con `log_message('debug', ...)` y `log_message('error', ...)`
   - Útil para debugging y monitoreo

2. **Compatibilidad:**
   - El sistema soporta tanto peticiones AJAX como peticiones normales (fallback)
   - Si no es AJAX, redirige con flash message

3. **Mensajes de Error:**
   - Todos los mensajes de error son específicos y descriptivos
   - Se muestran tanto en alertas JavaScript como en flash messages

---

## ✅ Conclusión

El flujo de cambio de contraseña está bien estructurado con:
- ✅ Validaciones múltiples (frontend y backend)
- ✅ Comunicación segura con la API (Bearer Token)
- ✅ Hash seguro de contraseñas (bcrypt)
- ✅ Manejo de errores robusto
- ✅ Cierre de sesión automático después del cambio
- ✅ Feedback visual al usuario (alertas SweetAlert)

**Mejora sugerida:** Considerar implementar un rollback o transacción si la actualización de `users` falla después de actualizar `companies`.

