# API v3 - Interfaz para Aplicación Móvil

## Descripción

La API v3 está optimizada específicamente para aplicaciones móviles, proporcionando:

- **Respuestas optimizadas**: Formato compacto con solo los datos necesarios
- **Autenticación mejorada**: Tokens con refresh automático
- **Sincronización offline**: Endpoints para manejar datos offline
- **Notificaciones push**: Soporte para notificaciones en tiempo real
- **Optimización de recursos**: Imágenes y datos optimizados para móvil

## Base URL

La base URL depende del entorno donde se ejecute la API:

- **Desarrollo local**: `http://localhost/api/v3`
- **Test**: `https://test.fullvendor.com/api/v3`
- **Demo**: `https://demo.fullvendor.com/api/v3`
- **Producción**: `https://app.fullvendor.com/api/v3`

La URL base se configura mediante la variable de entorno `APP_URL` en el archivo `.env`:

```env
APP_URL=http://localhost
# o
APP_URL=https://app.fullvendor.com
```

Para obtener la URL base completa de la API v3, usa: `{APP_URL}/api/v3`

## Autenticación

La API v3 utiliza **Laravel Sanctum** para autenticación mediante tokens Bearer.

### Login

```http
POST {APP_URL}/api/v3/auth/login
Content-Type: application/json

{
  "email": "usuario@example.com",
  "password": "password123"
}
```

**Ejemplo:**
```http
POST http://localhost/api/v3/auth/login
Content-Type: application/json

{
  "email": "usuario@example.com",
  "password": "password123"
}
```

**Nota:** Solo se requieren `email` y `password`. El sistema automáticamente usa `mobile-app` como nombre del token.

**Respuesta exitosa:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Juan Pérez",
      "email": "usuario@example.com",
      "companies": [
        {
          "id": 1,
          "name": "Mi Empresa"
        }
      ]
    },
    "token": "1|abcdef123456...",
    "token_type": "Bearer"
  },
  "message": "Login exitoso"
}
```

### Uso del Token

Incluir el token en el header de todas las peticiones autenticadas:

```http
Authorization: Bearer 1|abcdef123456...
```

## Endpoints Principales

### Autenticación

- `POST /auth/login` - Iniciar sesión
- `POST /auth/register` - Registrar nuevo usuario
- `GET /auth/me` - Obtener usuario actual
- `POST /auth/refresh` - Renovar token
- `POST /auth/logout` - Cerrar sesión
- `PUT /auth/profile` - Actualizar perfil
- `PUT /auth/password` - Cambiar contraseña

### Compañías

- `GET /companies` - Listar compañías del usuario
- `GET /companies/{id}` - Obtener compañía específica
- `GET /companies/{id}/catalogs` - Listar catálogos de una compañía

### Categorías

- `GET /categories` - Listar categorías (requiere `company_id`)
- `GET /categories/{id}` - Obtener categoría específica
- `GET /categories/{id}/products` - Productos de una categoría

### Productos

- `GET /products` - Listar productos
- `GET /products/search` - Buscar productos
- `GET /products/{id}` - Obtener producto específico
- `GET /products/{id}/images` - Imágenes del producto

### Carrito

- `GET /cart` - Obtener carritos del usuario
- `GET /cart/{id}` - Obtener carrito específico
- `POST /cart` - Crear nuevo carrito
- `POST /cart/items` - Agregar item al carrito
- `PUT /cart/items/{id}` - Actualizar item
- `DELETE /cart/items/{id}` - Eliminar item
- `POST /cart/{id}/checkout` - Realizar pedido

### Órdenes

- `GET /orders` - Listar órdenes
- `GET /orders/{id}` - Obtener orden específica
- `GET /orders/{id}/details` - Detalles de la orden
- `GET /orders/{id}/status` - Estado de la orden

### Dispositivo y Notificaciones

- `POST /device/register` - Registrar dispositivo para push
- `PUT /device/token` - Actualizar token de push
- `DELETE /device/unregister` - Desregistrar dispositivo
- `GET /notifications` - Listar notificaciones
- `GET /notifications/unread` - Notificaciones no leídas
- `PUT /notifications/{id}/read` - Marcar como leída

### Sincronización

- `GET /sync/status` - Estado de sincronización
- `POST /sync/pull` - Sincronizar datos del servidor
- `POST /sync/push` - Enviar cambios locales

## Formato de Respuesta

### Respuesta Exitosa

```json
{
  "success": true,
  "data": {
    // Datos de la respuesta
  },
  "message": "Mensaje opcional"
}
```

### Respuesta de Error

```json
{
  "success": false,
  "message": "Mensaje de error",
  "errors": {
    // Detalles de errores de validación (opcional)
  }
}
```

### Respuesta Paginada

```json
{
  "success": true,
  "data": {
    "items": [
      // Array de items
    ],
    "meta": {
      "total": 100,
      "per_page": 20,
      "current_page": 1,
      "last_page": 5
    }
  }
}
```

## Códigos de Estado HTTP

- `200` - Éxito
- `201` - Creado exitosamente
- `400` - Error en la petición
- `401` - No autenticado
- `403` - No autorizado
- `404` - No encontrado
- `422` - Error de validación
- `500` - Error del servidor

## Ejemplos de Uso

> **Nota:** Reemplaza `{APP_URL}` con la URL base de tu entorno (ver sección Base URL).

### Obtener Categorías

```http
GET {APP_URL}/api/v3/categories?company_id=1
Authorization: Bearer 1|abcdef123456...
```

**Ejemplo:**
```http
GET http://localhost/api/v3/categories?company_id=1
Authorization: Bearer 1|abcdef123456...
```

### Buscar Productos

```http
GET {APP_URL}/api/v3/products/search?q=producto&company_id=1
Authorization: Bearer 1|abcdef123456...
```

**Ejemplo:**
```http
GET http://localhost/api/v3/products/search?q=producto&company_id=1
Authorization: Bearer 1|abcdef123456...
```

### Agregar Item al Carrito

```http
POST {APP_URL}/api/v3/cart/items
Authorization: Bearer 1|abcdef123456...
Content-Type: application/json

{
  "cart_id": 1,
  "product_id": 123,
  "quantity": 2,
  "price": 99.99
}
```

**Ejemplo:**
```http
POST http://localhost/api/v3/cart/items
Authorization: Bearer 1|abcdef123456...
Content-Type: application/json

{
  "cart_id": 1,
  "product_id": 123,
  "quantity": 2,
  "price": 99.99
}
```

## Notas de Implementación

1. **Optimización de Imágenes**: Las URLs de imágenes incluyen parámetros de tamaño para optimizar el ancho de banda móvil.

2. **Paginación**: Todos los endpoints de listado soportan paginación mediante parámetros `page` y `per_page`.

3. **Filtros**: Muchos endpoints soportan filtros mediante query parameters.

4. **Sincronización Offline**: Los endpoints de sync permiten manejar datos cuando la app está offline.

5. **Notificaciones Push**: Requiere registrar el dispositivo primero mediante `/device/register`.

## Próximos Pasos

- [ ] Implementar lógica completa en todos los controladores
- [ ] Agregar validaciones específicas para móvil
- [ ] Implementar sistema de notificaciones push
- [ ] Agregar documentación OpenAPI/Swagger
- [ ] Implementar rate limiting específico para móvil
- [ ] Agregar tests unitarios y de integración

