# Controllers

This document provides an overview of the controllers used in the backend.

## Controller Structure

Controllers in the Laravel backend follow the RESTful resource controller pattern, with standard methods for CRUD operations:

- `index()`: List resources
- `store()`: Create a new resource
- `show()`: Show a specific resource
- `update()`: Update a specific resource
- `destroy()`: Delete a specific resource

Additionally, controllers may have custom methods for specific functionality.

## Main Controllers

### UserController

Handles user management operations.

**Methods:**
- `token()`: Generates an authentication token for the user
- `index()`: Lists all users
- `store()`: Creates a new user
- `show()`: Shows a specific user
- `update()`: Updates a specific user
- `destroy()`: Deletes a user
- `customer()`: Gets the customer associated with a user
- `seller()`: Gets the seller associated with a user

### CatalogController

Manages catalogs for products.

**Methods:**
- `categories()`: Gets categories in a catalog
- `products()`: Gets products in a catalog for a specific customer
- `sendMailCatalog()`: Sends a catalog via email
- `assignProducts()`: Assigns products to a catalog
- `removeProducts()`: Removes products from a catalog
- `priceListExport()`: Exports a price list for a specific customer

### CatalogCartController

Manages the shopping cart functionality for catalogs.

**Methods:**
- `addProduct()`: Adds a product to the cart
- `quickAddProduct()`: Quickly adds a product to the cart
- `cartIds()`: Gets cart IDs for a company
- `openCarts()`: Gets open carts for a company
- `openCart()`: Gets the open cart for a specific customer
- `hasPendings()`: Checks if a customer has pending items
- `getByToken()`: Gets a cart by its token
- `destroyDetails()`: Deletes cart details
- `deleteProduct()`: Deletes a product from a cart
- `info()`: Gets information about a cart
- `placeOrder()`: Places an order from a cart

### CategoryController

Manages product categories.

**Methods:**
- `hasProducts()`: Checks if a category has products
- `products()`: Gets products in a category
- `assignProducts()`: Assigns products to a category

### ProductController

Manages products.

**Methods:**
- `index()`: Lists all products
- `store()`: Creates a new product
- `show()`: Shows a specific product
- `update()`: Updates a specific product
- `destroy()`: Deletes a product
- `images()`: Gets images for a product
- `assignCategories()`: Assigns categories to a product
- `removeCategories()`: Removes categories from a product

### OrderController

Manages orders.

**Methods:**
- `index()`: Lists all orders
- `store()`: Creates a new order
- `show()`: Shows a specific order
- `update()`: Updates a specific order
- `destroy()`: Deletes an order
- `details()`: Gets details of an order
- `statusHistory()`: Gets the status history of an order
- `updateStatus()`: Updates the status of an order
- `addNote()`: Adds a note to an order

### CustomerController

Manages customers.

**Methods:**
- `index()`: Lists all customers
- `store()`: Creates a new customer
- `show()`: Shows a specific customer
- `update()`: Updates a specific customer
- `destroy()`: Deletes a customer
- `orders()`: Gets orders for a customer
- `catalogs()`: Gets catalogs for a customer

### TranslationController

Handles translation functionality.

**Methods:**
- `t()`: Translates a string
- `langmdl()`: Gets a language module
- `modules()`: Gets all language modules

### NavbarRolIdController

Manages navigation bar items based on user roles.

**Methods:**
- `index()`: Lists all navbar items for a role
- `store()`: Creates a new navbar item for a role
- `show()`: Shows a specific navbar item for a role
- `update()`: Updates a specific navbar item for a role
- `destroy()`: Deletes a navbar item for a role

## Controller Middleware

Controllers use middleware to handle cross-cutting concerns:

- **auth:sanctum**: Ensures the user is authenticated
- **can:ability**: Ensures the user has the required ability
- **throttle**: Limits the rate of requests
- **validate**: Validates request data

## Request Validation

Controllers use form request classes for validation:

- **StoreUserRequest**: Validates user creation requests
- **UpdateUserRequest**: Validates user update requests
- **StoreCatalogRequest**: Validates catalog creation requests
- **UpdateCatalogRequest**: Validates catalog update requests

## Response Format

Controllers return responses in a consistent format:

- **Success responses**: HTTP 200 OK with data
- **Creation responses**: HTTP 201 Created with data
- **Error responses**: HTTP 4xx or 5xx with error message
- **Validation errors**: HTTP 422 Unprocessable Entity with validation errors

## API Documentation

Controllers use OpenAPI annotations to document the API endpoints, which can be used to generate API documentation.