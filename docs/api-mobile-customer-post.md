# API POST /api/v2/customer - Guía para Aplicación Móvil

## Endpoints Disponibles

### 1. Endpoint Público (Sin Autenticación)
```
POST /api/v2/customer/add
```
**No requiere token de autenticación** - Ideal para registro público desde aplicaciones móviles.

### 2. Endpoint Protegido (Con Autenticación)
```
POST /api/v2/customer
```
**Requiere autenticación mediante Bearer Token** (Laravel Sanctum) - Para usuarios autenticados.

---

## Endpoint Público: POST /api/v2/customer/add

### Autenticación
**NO requiere autenticación** - Este endpoint es público y puede ser consumido sin token.

### Headers Requeridos
```
Accept: application/json
Content-Type: application/json
```

### URL Completa
```
POST https://tu-dominio.com/api/v2/customer/add
```

### Ejemplo de Petición (Sin Token)
```bash
curl -X POST https://tu-dominio.com/api/v2/customer/add \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "name": "Juan Pérez",
    "business_name": "Empresa XYZ S.A.",
    "tax_id": "12345678901",
    "phone": "+1234567890",
    "sellers": [1, 2],
    "term_id": 1,
    "group_id": 1,
    "commercial_address": "Av. Principal 123"
  }'
```

---

## Endpoint Protegido: POST /api/v2/customer

### Autenticación
La API requiere autenticación mediante **Bearer Token** (Laravel Sanctum).

### Headers Requeridos
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Obtener Token
Primero debes autenticarte para obtener el token:
```
POST /api/v2/user/token
Body: {
  "email": "usuario@ejemplo.com",
  "password": "contraseña"
}
```

## Estructura de la Petición

### URL Completa
```
POST https://tu-dominio.com/api/v2/customer
```

### Headers
```json
{
  "Authorization": "Bearer {tu_token_aqui}",
  "Accept": "application/json",
  "Content-Type": "application/json"
}
```

### Body (JSON)

#### Campos Requeridos
```json
{
  "company_id": 1,
  "name": "Nombre del Cliente",
  "business_name": "Razón Social S.A.",
  "tax_id": "12345678901",
  "phone": "+1234567890",
  "sellers": [1, 2],
  "term_id": 1,
  "group_id": 1,
  "commercial_address": "Calle Principal 123"
}
```

#### Campos Opcionales
```json
{
  "discount": 10.5,
  "email": "cliente@ejemplo.com",
  "cell_phone": "+1234567890",
  "catalog_emails": true,
  "seller_assign_catalog": 1,
  "commercial_delivery_address": "Calle Entrega 456",
  "commercial_country": 1,
  "commercial_state": 5,
  "commercial_city": 10,
  "commercial_zip_code": "12345",
  "commercial_zone": "Zona Norte",
  "dispatch_address": "Calle Despacho 789",
  "dispatch_state": 5,
  "dispatch_country": 1,
  "dispatch_city": 10,
  "dispatch_zip_code": "54321",
  "dispatch_zone": "Zona Sur",
  "dispatch_shipping_notes": "Notas de envío especiales"
}
```

### Ejemplo Completo de Petición

```json
{
  "company_id": 1,
  "name": "Juan Pérez",
  "business_name": "Empresa XYZ S.A.",
  "tax_id": "12345678901",
  "phone": "+1234567890",
  "cell_phone": "+0987654321",
  "email": "juan.perez@empresa.com",
  "discount": 15.0,
  "catalog_emails": true,
  "sellers": [1, 2, 3],
  "seller_assign_catalog": 1,
  "term_id": 1,
  "group_id": 1,
  "commercial_address": "Av. Principal 123, Piso 5",
  "commercial_delivery_address": "Av. Entrega 456",
  "commercial_country": 1,
  "commercial_state": 5,
  "commercial_city": 10,
  "commercial_zip_code": "12345",
  "commercial_zone": "Zona Norte",
  "dispatch_address": "Av. Despacho 789",
  "dispatch_state": 5,
  "dispatch_country": 1,
  "dispatch_city": 10,
  "dispatch_zip_code": "54321",
  "dispatch_zone": "Zona Sur",
  "dispatch_shipping_notes": "Entregar solo en horario laboral"
}
```

## Validaciones

### Campos Requeridos y Reglas

| Campo | Tipo | Validación | Descripción |
|-------|------|------------|-------------|
| `company_id` | integer | required, exists:companies,company_id | ID de la empresa |
| `name` | string | required, max:255 | Nombre del cliente |
| `business_name` | string | required, max:255, unique | Razón social (debe ser único) |
| `tax_id` | string | required, max:255 | Número de identificación fiscal |
| `phone` | string | required, max:20 | Teléfono principal |
| `sellers` | array | required, min:1 | Array de IDs de vendedores |
| `sellers.*` | integer | numeric, distinct, exists:sellers,id | Cada ID debe existir en sellers |
| `term_id` | integer | required, exists:terms_of_sales,term_id | ID del término de venta |
| `group_id` | integer | required, exists:customer_groups,group_id | ID del grupo de clientes |
| `commercial_address` | string | required, max:255 | Dirección comercial |

### Campos Opcionales

| Campo | Tipo | Validación | Descripción |
|-------|------|------------|-------------|
| `discount` | numeric | nullable, between:0,100 | Descuento (0-100) |
| `email` | string | nullable, email, max:255 | Email del cliente |
| `cell_phone` | string | nullable, max:20 | Teléfono celular |
| `catalog_emails` | boolean | nullable | Recibir catálogos por email |
| `seller_assign_catalog` | integer | nullable, exists:sellers,id | Vendedor asignado para catálogo |
| `commercial_delivery_address` | string | nullable, max:255 | Dirección de entrega comercial |
| `commercial_country` | integer | nullable, exists:countries,id | País comercial |
| `commercial_state` | integer | nullable, exists:states,id | Estado/Provincia comercial |
| `commercial_city` | integer | nullable, exists:cities,id | Ciudad comercial |
| `commercial_zip_code` | string | nullable, max:20 | Código postal comercial |
| `commercial_zone` | string | nullable, max:100 | Zona comercial |
| `dispatch_address` | string | nullable, max:255 | Dirección de despacho |
| `dispatch_state` | integer | nullable, exists:states,id | Estado de despacho |
| `dispatch_country` | integer | nullable, exists:countries,id | País de despacho |
| `dispatch_city` | integer | nullable, exists:cities,id | Ciudad de despacho |
| `dispatch_zip_code` | string | nullable, max:20 | Código postal de despacho |
| `dispatch_zone` | string | nullable, max:100 | Zona de despacho |
| `dispatch_shipping_notes` | string | nullable, max:1000 | Notas de envío |

## Respuestas

### Éxito (200 OK)
```json
{
  "data": {
    "customer_id": 123,
    "name": "Juan Pérez",
    "business_name": "Empresa XYZ S.A.",
    "tax_id": "12345678901",
    "email": "juan.perez@empresa.com",
    "phone": "+1234567890",
    "company_id": 1,
    "term_id": 1,
    "group_id": 1,
    "created_at": "2026-01-26T10:30:00.000000Z",
    "updated_at": "2026-01-26T10:30:00.000000Z"
  },
  "message": "Resource created successfully"
}
```

### Error de Validación (422 Unprocessable Entity)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "business_name": [
      "The business name has already been taken."
    ],
    "sellers": [
      "The sellers field is required."
    ],
    "commercial_address": [
      "The commercial address field is required."
    ]
  }
}
```

### Error de Autenticación (401 Unauthorized)
```json
{
  "message": "Unauthenticated"
}
```

### Error de Permisos (403 Forbidden)
```json
{
  "message": "This action is unauthorized."
}
```

## Ejemplos de Código

### JavaScript/TypeScript (React Native / Expo)
```javascript
const createCustomer = async (customerData, token) => {
  try {
    const response = await fetch('https://tu-dominio.com/api/v2/customer', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(customerData),
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Error al crear cliente');
    }

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
};

// Uso
const customerData = {
  company_id: 1,
  name: "Juan Pérez",
  business_name: "Empresa XYZ S.A.",
  tax_id: "12345678901",
  phone: "+1234567890",
  sellers: [1, 2],
  term_id: 1,
  group_id: 1,
  commercial_address: "Av. Principal 123"
};

createCustomer(customerData, 'tu_token_aqui')
  .then(result => console.log('Cliente creado:', result))
  .catch(error => console.error('Error:', error));
```

### Kotlin (Android)
```kotlin
import okhttp3.*
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.RequestBody.Companion.toRequestBody
import org.json.JSONObject

fun createCustomer(customerData: Map<String, Any>, token: String): String {
    val client = OkHttpClient()
    
    val json = JSONObject(customerData).toString()
    val mediaType = "application/json; charset=utf-8".toMediaType()
    val body = json.toRequestBody(mediaType)
    
    val request = Request.Builder()
        .url("https://tu-dominio.com/api/v2/customer")
        .post(body)
        .addHeader("Authorization", "Bearer $token")
        .addHeader("Accept", "application/json")
        .addHeader("Content-Type", "application/json")
        .build()
    
    val response = client.newCall(request).execute()
    return response.body?.string() ?: ""
}

// Uso
val customerData = mapOf(
    "company_id" to 1,
    "name" to "Juan Pérez",
    "business_name" to "Empresa XYZ S.A.",
    "tax_id" to "12345678901",
    "phone" to "+1234567890",
    "sellers" to listOf(1, 2),
    "term_id" to 1,
    "group_id" to 1,
    "commercial_address" to "Av. Principal 123"
)

val result = createCustomer(customerData, "tu_token_aqui")
println(result)
```

### Swift (iOS)
```swift
import Foundation

func createCustomer(customerData: [String: Any], token: String) async throws -> Data {
    let url = URL(string: "https://tu-dominio.com/api/v2/customer")!
    var request = URLRequest(url: url)
    request.httpMethod = "POST"
    request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
    request.setValue("application/json", forHTTPHeaderField: "Accept")
    request.setValue("application/json", forHTTPHeaderField: "Content-Type")
    
    let jsonData = try JSONSerialization.data(withJSONObject: customerData)
    request.httpBody = jsonData
    
    let (data, response) = try await URLSession.shared.data(for: request)
    
    guard let httpResponse = response as? HTTPURLResponse,
          httpResponse.statusCode == 200 else {
        throw URLError(.badServerResponse)
    }
    
    return data
}

// Uso
let customerData: [String: Any] = [
    "company_id": 1,
    "name": "Juan Pérez",
    "business_name": "Empresa XYZ S.A.",
    "tax_id": "12345678901",
    "phone": "+1234567890",
    "sellers": [1, 2],
    "term_id": 1,
    "group_id": 1,
    "commercial_address": "Av. Principal 123"
]

Task {
    do {
        let result = try await createCustomer(customerData: customerData, token: "tu_token_aqui")
        print("Cliente creado:", result)
    } catch {
        print("Error:", error)
    }
}
```

### Dart (Flutter)
```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

Future<Map<String, dynamic>> createCustomer(
  Map<String, dynamic> customerData,
  String token,
) async {
  final url = Uri.parse('https://tu-dominio.com/api/v2/customer');
  
  final response = await http.post(
    url,
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    body: jsonEncode(customerData),
  );

  if (response.statusCode == 200) {
    return jsonDecode(response.body);
  } else {
    throw Exception('Error al crear cliente: ${response.body}');
  }
}

// Uso
final customerData = {
  'company_id': 1,
  'name': 'Juan Pérez',
  'business_name': 'Empresa XYZ S.A.',
  'tax_id': '12345678901',
  'phone': '+1234567890',
  'sellers': [1, 2],
  'term_id': 1,
  'group_id': 1,
  'commercial_address': 'Av. Principal 123',
};

try {
  final result = await createCustomer(customerData, 'tu_token_aqui');
  print('Cliente creado: $result');
} catch (e) {
  print('Error: $e');
}
```

## Notas Importantes

### Para `/api/v2/customer/add` (Endpoint Público)
1. **NO requiere autenticación**: Puedes consumir este endpoint sin token
2. **Content-Type**: Debe ser `application/json`
3. **business_name**: Debe ser único en la base de datos
4. **sellers**: Debe ser un array con al menos un vendedor válido
5. **IDs de Referencia**: Todos los IDs (company_id, term_id, group_id, sellers, etc.) deben existir en la base de datos
6. **Dirección Comercial**: Es obligatoria (`commercial_address`)

### Para `/api/v2/customer` (Endpoint Protegido)
1. **Autenticación**: Siempre incluye el token Bearer en el header `Authorization`
2. **Content-Type**: Debe ser `application/json`
3. **business_name**: Debe ser único en la base de datos
4. **sellers**: Debe ser un array con al menos un vendedor válido
5. **IDs de Referencia**: Todos los IDs (company_id, term_id, group_id, sellers, etc.) deben existir en la base de datos
6. **Dirección Comercial**: Es obligatoria (`commercial_address`)
7. **Permisos**: El usuario debe tener permisos para crear clientes (`can('create', Customer::class)`)

## Manejo de Errores

Siempre verifica el código de estado HTTP:
- **200**: Éxito
- **401**: No autenticado - solo aplica para `/api/v2/customer` (endpoint protegido)
- **403**: Sin permisos - solo aplica para `/api/v2/customer` (endpoint protegido)
- **422**: Error de validación - revisa los campos en `errors` (aplica a ambos endpoints)
- **500**: Error del servidor

## Testing con cURL

### Endpoint Público (Sin Token)
```bash
curl -X POST https://tu-dominio.com/api/v2/customer/add \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "name": "Juan Pérez",
    "business_name": "Empresa XYZ S.A.",
    "tax_id": "12345678901",
    "phone": "+1234567890",
    "sellers": [1, 2],
    "term_id": 1,
    "group_id": 1,
    "commercial_address": "Av. Principal 123"
  }'
```

### Endpoint Protegido (Con Token)
```bash
curl -X POST https://tu-dominio.com/api/v2/customer \
  -H "Authorization: Bearer tu_token_aqui" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "name": "Juan Pérez",
    "business_name": "Empresa XYZ S.A.",
    "tax_id": "12345678901",
    "phone": "+1234567890",
    "sellers": [1, 2],
    "term_id": 1,
    "group_id": 1,
    "commercial_address": "Av. Principal 123"
  }'
```

## Recomendación

- **Usa `/api/v2/customer/add`** si estás desarrollando una aplicación móvil que permite registro público de clientes sin necesidad de autenticación previa.
- **Usa `/api/v2/customer`** si el usuario ya está autenticado en tu aplicación y necesitas mantener la seguridad basada en permisos.
