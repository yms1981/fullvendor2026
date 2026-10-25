# API Endpoints

This document provides an overview of the available API endpoints in the backend.

## API Version

All endpoints are prefixed with `/api/v2/`.

## Authentication

### Get Token

```
GET /user/token
```

Generates an authentication token for the user.

### Get Current User

```
GET /user
```

Returns the currently authenticated user's information.

## User Management

### List Users

```
GET /users
```

Returns a list of all users.

### Get User

```
GET /users/{userId}
```

Returns information about a specific user.

### Create User

```
POST /users
```

Creates a new user.

### Update User

```
PUT /users/{userId}
```

Updates an existing user.

### Delete User

```
DELETE /users/{userId}
```

Deletes a user.

### Get User Customer

```
GET /users/{userId}/customer
```

Returns the customer associated with a user.

### Get User Seller

```
GET /users/{userId}/seller
```

Returns the seller associated with a user.

## Catalog Management

### Get Catalog Categories

```
GET /catalog/{catalogId}/categories
```

Returns the categories in a catalog.

### Get Catalog Products

```
GET /catalog/{catalogId}/customer/{customerId}/products
```

Returns the products in a catalog for a specific customer.

### Send Catalog

```
POST /catalog/send
```

Sends a catalog via email.

### Assign Products to Catalog

```
PUT /catalog/{catalogId}/products
```

Assigns products to a catalog.

### Remove Products from Catalog

```
DELETE /catalog/{catalogId}/products
```

Removes products from a catalog.

### Export Price List

```
GET /catalog/company/{companyId}/customer/{customerId}/price-list-export
```

Exports a price list for a specific customer.

## Catalog Cart

### Add Product to Cart

```
POST /catalogcart/addProduct
```

Adds a product to the catalog cart.

### Quick Add Product

```
POST /catalogcart/quickAddProduct
```

Quickly adds a product to the catalog cart.

### Get Cart IDs

```
GET /catalogcart/company/{companyId}/cartIds
```

Returns the cart IDs for a company.

### Get Open Carts

```
GET /catalogcart/company/{companyId}/openCarts
```

Returns the open carts for a company.

### Get Open Cart for Customer

```
GET /catalogcart/company/{companyId}/customer/{customerId}
```

Returns the open cart for a specific customer.

### Check Pending Items

```
GET /catalogcart/company/{companyId}/customer/{customerId}/hasPendings
```

Checks if a customer has pending items in their cart.

### Get Cart by Token

```
GET /catalogcart/token-{token}
```

Returns a cart by its token.

### Delete Cart Details

```
DELETE /catalogcart/{catalogCartId}/details
```

Deletes the details of a catalog cart.

### Delete Product from Cart

```
DELETE /catalogcart/{orderId}/delete-product/{productId}
```

Deletes a product from a cart.

### Get Cart Info

```
GET /catalogcart/{orderId}/info
```

Returns information about a cart.

### Place Order

```
PUT /catalogcart/{order}/place-order
```

Places an order from a cart.

## Category Management

### Check if Category Has Products

```
GET /category/{categoryId}/hasProducts
```

Checks if a category has products.

### Get Category Products

```
GET /category/{categoryId}/products
```

Returns the products in a category.

### Assign Products to Category

```
PUT /category/{categoryId}/products
```

Assigns products to a category.

## Translation

### Translate String

```
GET /t/{string}
```

Translates a string.

### Get Language Module

```
GET /langmdl/{string}
```

Returns a language module.

### Get Modules

```
GET /langmodules
```

Returns all language modules.

## Note

This is not an exhaustive list of all endpoints. For a complete list, refer to the `routes/api.php` file in the backend codebase.