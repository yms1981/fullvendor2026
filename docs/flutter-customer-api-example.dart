// Ejemplo completo para Flutter - Consumir API POST /api/v2/customer/add
// Usando Dio para peticiones HTTP

import 'dart:convert';
import 'package:dio/dio.dart';

// ============================================
// 1. MODELO DE DATOS (Customer Model)
// ============================================

class Customer {
  final int? customerId;
  final int companyId;
  final String name;
  final String businessName;
  final String taxId;
  final String phone;
  final String? email;
  final String? cellPhone;
  final double? discount;
  final bool? catalogEmails;
  final List<int> sellers;
  final int? sellerAssignCatalog;
  final int termId;
  final int groupId;
  final String commercialAddress;
  final String? commercialDeliveryAddress;
  final int? commercialCountry;
  final int? commercialState;
  final int? commercialCity;
  final String? commercialZipCode;
  final String? commercialZone;
  final String? dispatchAddress;
  final int? dispatchState;
  final int? dispatchCountry;
  final int? dispatchCity;
  final String? dispatchZipCode;
  final String? dispatchZone;
  final String? dispatchShippingNotes;

  Customer({
    this.customerId,
    required this.companyId,
    required this.name,
    required this.businessName,
    required this.taxId,
    required this.phone,
    this.email,
    this.cellPhone,
    this.discount,
    this.catalogEmails,
    required this.sellers,
    this.sellerAssignCatalog,
    required this.termId,
    required this.groupId,
    required this.commercialAddress,
    this.commercialDeliveryAddress,
    this.commercialCountry,
    this.commercialState,
    this.commercialCity,
    this.commercialZipCode,
    this.commercialZone,
    this.dispatchAddress,
    this.dispatchState,
    this.dispatchCountry,
    this.dispatchCity,
    this.dispatchZipCode,
    this.dispatchZone,
    this.dispatchShippingNotes,
  });

  // Convertir a JSON para enviar a la API
  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = {
      'company_id': companyId,
      'name': name,
      'business_name': businessName,
      'tax_id': taxId,
      'phone': phone,
      'sellers': sellers,
      'term_id': termId,
      'group_id': groupId,
      'commercial_address': commercialAddress,
    };

    // Agregar campos opcionales solo si tienen valor
    if (email != null) data['email'] = email;
    if (cellPhone != null) data['cell_phone'] = cellPhone;
    if (discount != null) data['discount'] = discount;
    if (catalogEmails != null) data['catalog_emails'] = catalogEmails;
    if (sellerAssignCatalog != null) data['seller_assign_catalog'] = sellerAssignCatalog;
    if (commercialDeliveryAddress != null) data['commercial_delivery_address'] = commercialDeliveryAddress;
    if (commercialCountry != null) data['commercial_country'] = commercialCountry;
    if (commercialState != null) data['commercial_state'] = commercialState;
    if (commercialCity != null) data['commercial_city'] = commercialCity;
    if (commercialZipCode != null) data['commercial_zip_code'] = commercialZipCode;
    if (commercialZone != null) data['commercial_zone'] = commercialZone;
    if (dispatchAddress != null) data['dispatch_address'] = dispatchAddress;
    if (dispatchState != null) data['dispatch_state'] = dispatchState;
    if (dispatchCountry != null) data['dispatch_country'] = dispatchCountry;
    if (dispatchCity != null) data['dispatch_city'] = dispatchCity;
    if (dispatchZipCode != null) data['dispatch_zip_code'] = dispatchZipCode;
    if (dispatchZone != null) data['dispatch_zone'] = dispatchZone;
    if (dispatchShippingNotes != null) data['dispatch_shipping_notes'] = dispatchShippingNotes;

    return data;
  }

  // Crear desde JSON (respuesta de la API)
  factory Customer.fromJson(Map<String, dynamic> json) {
    return Customer(
      customerId: json['customer_id'],
      companyId: json['company_id'] ?? json['company_id'],
      name: json['name'] ?? '',
      businessName: json['business_name'] ?? '',
      taxId: json['tax_id'] ?? '',
      phone: json['phone'] ?? '',
      email: json['email'],
      cellPhone: json['cell_phone'],
      discount: json['discount']?.toDouble(),
      catalogEmails: json['catalog_emails'],
      sellers: json['sellers'] != null ? List<int>.from(json['sellers']) : [],
      sellerAssignCatalog: json['seller_assign_catalog'],
      termId: json['term_id'] ?? json['term_id'],
      groupId: json['group_id'] ?? json['group_id'],
      commercialAddress: json['commercial_address'] ?? '',
      commercialDeliveryAddress: json['commercial_delivery_address'],
      commercialCountry: json['commercial_country'],
      commercialState: json['commercial_state'],
      commercialCity: json['commercial_city'],
      commercialZipCode: json['commercial_zip_code'],
      commercialZone: json['commercial_zone'],
      dispatchAddress: json['dispatch_address'],
      dispatchState: json['dispatch_state'],
      dispatchCountry: json['dispatch_country'],
      dispatchCity: json['dispatch_city'],
      dispatchZipCode: json['dispatch_zip_code'],
      dispatchZone: json['dispatch_zone'],
      dispatchShippingNotes: json['dispatch_shipping_notes'],
    );
  }
}

// ============================================
// 2. RESPUESTA DE LA API
// ============================================

class CustomerResponse {
  final Customer? data;
  final String message;
  final Map<String, dynamic>? errors;

  CustomerResponse({
    this.data,
    required this.message,
    this.errors,
  });

  factory CustomerResponse.fromJson(Map<String, dynamic> json) {
    return CustomerResponse(
      data: json['data'] != null ? Customer.fromJson(json['data']) : null,
      message: json['message'] ?? '',
      errors: json['errors'],
    );
  }

  bool get isSuccess => data != null && errors == null;
}

// ============================================
// 3. SERVICIO DE API (Customer Service)
// ============================================

class CustomerService {
  // Cambiar esta URL por la URL base de tu API
  static const String baseUrl = 'https://tu-dominio.com/api/v2';

  // Endpoint público (sin autenticación)
  static const String publicEndpoint = '$baseUrl/customer/add';
  
  // Endpoint protegido (con autenticación)
  static const String protectedEndpoint = '$baseUrl/customer';

  // Instancia de Dio configurada
  static final Dio _dio = Dio(
    BaseOptions(
      baseUrl: baseUrl,
      connectTimeout: const Duration(seconds: 30),
      receiveTimeout: const Duration(seconds: 30),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ),
  );

  /// Crear cliente usando endpoint público (sin token)
  /// 
  /// [customer] - Objeto Customer con los datos del cliente
  /// 
  /// Retorna [CustomerResponse] con el resultado
  static Future<CustomerResponse> createCustomerPublic(Customer customer) async {
    try {
      final response = await _dio.post(
        '/customer/add',
        data: customer.toJson(),
      );

      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      return CustomerResponse(
        message: 'Error inesperado: ${e.toString()}',
        errors: {'error': ['Error desconocido: ${e.toString()}']},
      );
    }
  }

  /// Crear cliente usando endpoint protegido (con token)
  /// 
  /// [customer] - Objeto Customer con los datos del cliente
  /// [token] - Token de autenticación Bearer
  /// 
  /// Retorna [CustomerResponse] con el resultado
  static Future<CustomerResponse> createCustomerProtected(
    Customer customer,
    String token,
  ) async {
    try {
      final response = await _dio.post(
        '/customer',
        data: customer.toJson(),
        options: Options(
          headers: {
            'Authorization': 'Bearer $token',
          },
        ),
      );

      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      return CustomerResponse(
        message: 'Error inesperado: ${e.toString()}',
        errors: {'error': ['Error desconocido: ${e.toString()}']},
      );
    }
  }

  /// Manejar respuesta exitosa del servidor
  static CustomerResponse _handleResponse(Response response) {
    final Map<String, dynamic> responseData = response.data;

    if (response.statusCode == 200 || response.statusCode == 201) {
      // Éxito
      return CustomerResponse.fromJson(responseData);
    } else {
      // Otro código de estado
      return CustomerResponse(
        message: responseData['message'] ?? 'Error desconocido',
        errors: responseData['errors'],
      );
    }
  }

  /// Manejar errores de Dio
  static CustomerResponse _handleDioError(DioException error) {
    switch (error.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return CustomerResponse(
          message: 'Tiempo de espera agotado. Verifica tu conexión a internet.',
          errors: {'timeout': ['La petición tardó demasiado']},
        );

      case DioExceptionType.badResponse:
        final statusCode = error.response?.statusCode;
        final responseData = error.response?.data;

        if (statusCode == 422) {
          // Error de validación
          return CustomerResponse(
            message: responseData?['message'] ?? 'Error de validación',
            errors: responseData?['errors'],
          );
        } else if (statusCode == 401) {
          // No autenticado
          return CustomerResponse(
            message: 'No autenticado. Verifica tu token.',
            errors: {'auth': ['Token inválido o expirado']},
          );
        } else if (statusCode == 403) {
          // Sin permisos
          return CustomerResponse(
            message: 'No tienes permisos para realizar esta acción.',
            errors: {'permission': ['Acceso denegado']},
          );
        } else {
          // Otro error HTTP
          return CustomerResponse(
            message: responseData?['message'] ?? 'Error del servidor',
            errors: {'server': ['Error ${statusCode}: ${responseData?['message'] ?? 'Error desconocido'}']},
          );
        }

      case DioExceptionType.cancel:
        return CustomerResponse(
          message: 'La petición fue cancelada.',
          errors: {'cancel': ['Operación cancelada']},
        );

      case DioExceptionType.unknown:
      default:
        if (error.error.toString().contains('SocketException')) {
          return CustomerResponse(
            message: 'No hay conexión a internet. Verifica tu conexión.',
            errors: {'connection': ['Sin conexión a internet']},
          );
        }
        return CustomerResponse(
          message: 'Error de conexión: ${error.message}',
          errors: {'connection': ['No se pudo conectar con el servidor']},
        );
    }
  }

  /// Configurar interceptor para logging (opcional, útil para debug)
  static void setupInterceptors() {
    _dio.interceptors.add(
      LogInterceptor(
        request: true,
        requestHeader: true,
        requestBody: true,
        responseHeader: true,
        responseBody: true,
        error: true,
      ),
    );
  }

  /// Configurar interceptor para manejar tokens automáticamente
  static void setupAuthInterceptor(String Function() getToken) {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) {
          // Agregar token automáticamente si está disponible
          final token = getToken();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          return handler.next(options);
        },
        onError: (error, handler) {
          // Manejar errores de autenticación
          if (error.response?.statusCode == 401) {
            // Token expirado o inválido - aquí podrías refrescar el token
            // Por ahora solo pasamos el error
          }
          return handler.next(error);
        },
      ),
    );
  }
}

// ============================================
// 4. EJEMPLO DE USO EN WIDGET
// ============================================

import 'package:flutter/material.dart';

class CustomerFormScreen extends StatefulWidget {
  const CustomerFormScreen({Key? key}) : super(key: key);

  @override
  State<CustomerFormScreen> createState() => _CustomerFormScreenState();
}

class _CustomerFormScreenState extends State<CustomerFormScreen> {
  final _formKey = GlobalKey<FormState>();
  bool _isLoading = false;

  // Controladores para los campos del formulario
  final _nameController = TextEditingController();
  final _businessNameController = TextEditingController();
  final _taxIdController = TextEditingController();
  final _phoneController = TextEditingController();
  final _emailController = TextEditingController();
  final _cellPhoneController = TextEditingController();
  final _commercialAddressController = TextEditingController();

  // Valores seleccionados
  int? _selectedCompanyId = 1;
  int? _selectedTermId = 1;
  int? _selectedGroupId = 1;
  List<int> _selectedSellers = [1];
  double? _discount;

  @override
  void dispose() {
    _nameController.dispose();
    _businessNameController.dispose();
    _taxIdController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    _cellPhoneController.dispose();
    _commercialAddressController.dispose();
    super.dispose();
  }

  Future<void> _submitForm() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() {
      _isLoading = true;
    });

    // Crear objeto Customer con los datos del formulario
    final customer = Customer(
      companyId: _selectedCompanyId!,
      name: _nameController.text.trim(),
      businessName: _businessNameController.text.trim(),
      taxId: _taxIdController.text.trim(),
      phone: _phoneController.text.trim(),
      email: _emailController.text.trim().isEmpty 
          ? null 
          : _emailController.text.trim(),
      cellPhone: _cellPhoneController.text.trim().isEmpty 
          ? null 
          : _cellPhoneController.text.trim(),
      discount: _discount,
      sellers: _selectedSellers,
      termId: _selectedTermId!,
      groupId: _selectedGroupId!,
      commercialAddress: _commercialAddressController.text.trim(),
    );

    // Llamar al servicio (usando endpoint público)
    final response = await CustomerService.createCustomerPublic(customer);

    setState(() {
      _isLoading = false;
    });

    // Mostrar resultado
    if (!mounted) return;

    if (response.isSuccess) {
      // Éxito
      _showSuccessDialog(response.message);
      // Limpiar formulario
      _formKey.currentState!.reset();
    } else {
      // Error
      _showErrorDialog(response.message, response.errors);
    }
  }

  void _showSuccessDialog(String message) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Éxito'),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              Navigator.of(context).pop(); // Cerrar también el formulario
            },
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  void _showErrorDialog(String message, Map<String, dynamic>? errors) {
    String errorDetails = message;
    
    if (errors != null) {
      final errorList = <String>[];
      errors.forEach((key, value) {
        if (value is List) {
          errorList.addAll(value.map((e) => e.toString()));
        } else {
          errorList.add(value.toString());
        }
      });
      errorDetails += '\n\n${errorList.join('\n')}';
    }

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Error'),
        content: Text(errorDetails),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Crear Cliente'),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16.0),
          children: [
            // Nombre
            TextFormField(
              controller: _nameController,
              decoration: const InputDecoration(
                labelText: 'Nombre *',
                border: OutlineInputBorder(),
              ),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'El nombre es requerido';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Razón Social
            TextFormField(
              controller: _businessNameController,
              decoration: const InputDecoration(
                labelText: 'Razón Social *',
                border: OutlineInputBorder(),
              ),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'La razón social es requerida';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Tax ID
            TextFormField(
              controller: _taxIdController,
              decoration: const InputDecoration(
                labelText: 'Número de Identificación Fiscal *',
                border: OutlineInputBorder(),
              ),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'El número de identificación es requerido';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Teléfono
            TextFormField(
              controller: _phoneController,
              decoration: const InputDecoration(
                labelText: 'Teléfono *',
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.phone,
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'El teléfono es requerido';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Email
            TextFormField(
              controller: _emailController,
              decoration: const InputDecoration(
                labelText: 'Email',
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.emailAddress,
              validator: (value) {
                if (value != null && value.trim().isNotEmpty) {
                  if (!value.contains('@')) {
                    return 'Email inválido';
                  }
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Celular
            TextFormField(
              controller: _cellPhoneController,
              decoration: const InputDecoration(
                labelText: 'Celular',
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.phone,
            ),
            const SizedBox(height: 16),

            // Dirección Comercial
            TextFormField(
              controller: _commercialAddressController,
              decoration: const InputDecoration(
                labelText: 'Dirección Comercial *',
                border: OutlineInputBorder(),
              ),
              maxLines: 2,
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'La dirección comercial es requerida';
                }
                return null;
              },
            ),
            const SizedBox(height: 24),

            // Botón de envío
            ElevatedButton(
              onPressed: _isLoading ? null : _submitForm,
              style: ElevatedButton.styleFrom(
                padding: const EdgeInsets.symmetric(vertical: 16),
              ),
              child: _isLoading
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Text('Crear Cliente'),
            ),
          ],
        ),
      ),
    );
  }
}

// ============================================
// 5. EJEMPLO DE USO SIMPLE (Sin UI)
// ============================================

void ejemploUsoSimple() async {
  // Crear objeto Customer
  final customer = Customer(
    companyId: 1,
    name: 'Juan Pérez',
    businessName: 'Empresa XYZ S.A.',
    taxId: '12345678901',
    phone: '+1234567890',
    email: 'juan.perez@empresa.com',
    cellPhone: '+0987654321',
    discount: 15.0,
    catalogEmails: true,
    sellers: [1, 2],
    sellerAssignCatalog: 1,
    termId: 1,
    groupId: 1,
    commercialAddress: 'Av. Principal 123, Piso 5',
    commercialDeliveryAddress: 'Av. Entrega 456',
    commercialCountry: 1,
    commercialState: 5,
    commercialCity: 10,
    commercialZipCode: '12345',
    commercialZone: 'Zona Norte',
    dispatchAddress: 'Av. Despacho 789',
    dispatchState: 5,
    dispatchCountry: 1,
    dispatchCity: 10,
    dispatchZipCode: '54321',
    dispatchZone: 'Zona Sur',
    dispatchShippingNotes: 'Entregar solo en horario laboral',
  );

  // Llamar al servicio (endpoint público)
  final response = await CustomerService.createCustomerPublic(customer);

  // Manejar respuesta
  if (response.isSuccess) {
    print('✅ Cliente creado exitosamente!');
    print('ID: ${response.data?.customerId}');
    print('Mensaje: ${response.message}');
  } else {
    print('❌ Error al crear cliente');
    print('Mensaje: ${response.message}');
    if (response.errors != null) {
      print('Errores:');
      response.errors!.forEach((key, value) {
        print('  $key: $value');
      });
    }
  }
}

// ============================================
// 6. CONFIGURACIÓN EN pubspec.yaml
// ============================================

/*
dependencies:
  flutter:
    sdk: flutter
  dio: ^5.4.0  # Para hacer peticiones HTTP con Dio

# Luego ejecuta:
# flutter pub get
*/

// ============================================
// 7. EJEMPLO CON INTERCEPTORES (Opcional)
// ============================================

void ejemploConInterceptores() {
  // Configurar logging (solo en desarrollo)
  #if DEBUG
  CustomerService.setupInterceptors();
  #endif

  // Configurar interceptor de autenticación automática
  String? userToken; // Esto vendría de tu sistema de autenticación
  
  CustomerService.setupAuthInterceptor(() => userToken ?? '');

  // Ahora todas las peticiones incluirán el token automáticamente
  // (si usas createCustomerProtected, el token se agregará automáticamente)
}

// ============================================
// 8. EJEMPLO CON MANEJO DE TOKEN AUTOMÁTICO
// ============================================

class CustomerServiceWithAuth {
  static final Dio _dio = Dio(
    BaseOptions(
      baseUrl: CustomerService.baseUrl,
      connectTimeout: const Duration(seconds: 30),
      receiveTimeout: const Duration(seconds: 30),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ),
  );

  static String? _token;

  /// Establecer token de autenticación
  static void setToken(String? token) {
    _token = token;
  }

  /// Obtener token actual
  static String? getToken() => _token;

  /// Configurar interceptor para agregar token automáticamente
  static void _setupAuth() {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) {
          if (_token != null && _token!.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $_token';
          }
          return handler.next(options);
        },
      ),
    );
  }

  /// Crear cliente (usa token automáticamente si está configurado)
  static Future<CustomerResponse> createCustomer(Customer customer) async {
    _setupAuth();
    
    try {
      final response = await _dio.post(
        '/customer/add', // o '/customer' si quieres usar el protegido
        data: customer.toJson(),
      );

      return CustomerResponse.fromJson(response.data);
    } on DioException catch (e) {
      return CustomerService._handleDioError(e);
    } catch (e) {
      return CustomerResponse(
        message: 'Error inesperado: ${e.toString()}',
        errors: {'error': ['Error desconocido']},
      );
    }
  }
}

// Uso:
// CustomerServiceWithAuth.setToken('tu_token_aqui');
// final response = await CustomerServiceWithAuth.createCustomer(customer);
