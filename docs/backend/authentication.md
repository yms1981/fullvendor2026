# Authentication System

This document provides an overview of the authentication system used in the backend.

## Overview

The backend uses Laravel Sanctum for API authentication. Sanctum provides a lightweight authentication system for SPAs (Single Page Applications), mobile applications, and simple token-based APIs.

## Authentication Flow

1. The client sends a request to the `/api/v2/user/token` endpoint with valid credentials.
2. The server validates the credentials and returns an API token.
3. The client includes this token in the `Authorization` header of subsequent requests.
4. The server validates the token and identifies the user for each request.

## Token Generation

Tokens are generated using the `token()` method in the `UserController`:

```php
public function token(Request $request)
{
    // Validate credentials
    // Generate and return token
}
```

## Token Storage

Tokens are stored in the `personal_access_tokens` table, which includes:

- `tokenable_type`: The model type (usually `App\Models\User`)
- `tokenable_id`: The ID of the user
- `name`: The name of the token
- `token`: The hashed token
- `abilities`: The abilities granted to the token
- `last_used_at`: When the token was last used
- `expires_at`: When the token expires (if applicable)

## Middleware

The `auth:sanctum` middleware is used to protect routes that require authentication:

```php
Route::middleware('auth:sanctum')->group(function () {
    // Protected routes
});
```

## User Model

The `User` model uses the `HasApiTokens` trait to provide token functionality:

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    
    // ...
}
```

## Role-Based Access Control

The backend implements a role-based access control system:

### Roles

Users can have different roles in the system:

- Admin
- Manager
- Customer
- Warehouse
- Seller

### Role Assignment

Roles are assigned to users through the `company_user_role` pivot table, which links:

- `company_id`: The company the role applies to
- `user_id`: The user being assigned the role
- `role_id`: The role being assigned

### Role Checking

The `User` model provides methods to check if a user has a specific role:

- `isAdmin()`: Checks if the user is an admin
- `isManager($companyId)`: Checks if the user is a manager for a company
- `isCustomer($companyId)`: Checks if the user is a customer for a company
- `isWarehouse($companyId)`: Checks if the user is a warehouse user for a company
- `isSeller($companyId)`: Checks if the user is a seller for a company

### Abilities

Each role has specific abilities that determine what actions the user can perform. The `abilities()` method in the `User` model returns the abilities for a given role:

```php
public function abilities(string $role): array
{
    // Return abilities for the role
}
```

## Authorization

Authorization is handled using Laravel's built-in authorization features:

### Policies

Policies define the authorization rules for different actions on models:

- `UserPolicy`: Defines who can view, create, update, and delete users
- `CompanyPolicy`: Defines who can view, create, update, and delete companies
- `ProductPolicy`: Defines who can view, create, update, and delete products
- And more

### Middleware

The `can:ability` middleware is used to protect routes that require specific abilities:

```php
Route::middleware('can:manage-users')->group(function () {
    // Routes that require the manage-users ability
});
```

## Two-Factor Authentication

The backend supports two-factor authentication:

- `two_factor_secret`: Stores the secret key for 2FA
- `two_factor_recovery_codes`: Stores recovery codes for 2FA
- `two_factor_confirmed_at`: Records when 2FA was confirmed

## Security Considerations

- Tokens are hashed in the database
- Tokens can be scoped to specific abilities
- Tokens can be set to expire
- Failed login attempts are throttled to prevent brute force attacks
- Sensitive operations require re-authentication