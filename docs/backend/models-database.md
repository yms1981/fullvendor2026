# Models and Database Structure

This document provides an overview of the data models and database structure used in the backend.

## Database Schema

The database consists of numerous tables that store various types of data. Here's an overview of the main tables and their relationships:

### Users and Authentication

- **users**: Stores user account information
- **personal_access_tokens**: Stores API tokens for authentication
- **admin**: Stores admin user information
- **roles**: Defines user roles in the system
- **company_user_role**: Pivot table linking companies, users, and roles

### Companies and Customers

- **companies**: Stores company information
- **customers**: Stores customer information
- **customer_groups**: Groups customers for organizational purposes
- **customer_seller**: Links customers to sellers
- **customer_user**: Links customers to users

### Products and Categories

- **products**: Stores product information
- **categories**: Stores category information
- **category_product**: Links products to categories
- **product_images**: Stores product images
- **images**: Stores image information
- **units**: Defines units of measurement for products

### Orders and Carts

- **orders**: Stores order information
- **order_details**: Stores details of items in orders
- **order_status_history**: Tracks changes in order status
- **status_orders**: Defines possible order statuses
- **order_flows**: Defines order workflows
- **order_flow_transitions**: Defines transitions between order statuses
- **order_notes**: Stores notes related to orders

### Catalogs

- **catalog**: Stores catalog information
- **catalog_product**: Links products to catalogs
- **catalog_category**: Links categories to catalogs
- **catalog_sent_mail**: Tracks catalogs sent via email
- **catalog_request**: Stores catalog requests

### Addresses

- **addresses**: Stores address information
- **address_company**: Links addresses to companies
- **address_customer**: Links addresses to customers
- **countries**: Stores country information
- **states**: Stores state/province information
- **cities**: Stores city information

### Other

- **languages**: Stores language information
- **currency**: Stores currency information
- **settings**: Stores application settings
- **logs**: Stores system logs

## Key Models

### User Model

The `User` model represents a user in the system and has relationships with:

- Admin
- Customer
- Seller
- Roles
- Companies
- Orders
- Logs

Key methods:
- `isAdmin()`: Checks if the user is an admin
- `isManager()`: Checks if the user is a manager for a company
- `isCustomer()`: Checks if the user is a customer for a company
- `isWarehouse()`: Checks if the user is a warehouse user for a company
- `isSeller()`: Checks if the user is a seller for a company
- `rolesByCompany()`: Gets the user's roles for a specific company
- `abilities()`: Gets the user's abilities based on their role

### Company Model

The `Company` model represents a company in the system and has relationships with:

- Users
- Customers
- Products
- Orders
- Catalogs
- Addresses

### Product Model

The `Product` model represents a product in the system and has relationships with:

- Company
- Categories
- Images
- Order Details
- Catalogs

### Order Model

The `Order` model represents an order in the system and has relationships with:

- Company
- Customer
- User
- Order Details
- Order Status History
- Order Notes

### Category Model

The `Category` model represents a product category and has relationships with:

- Products
- Catalogs
- Parent Category (self-referential)
- Child Categories (self-referential)

## Database Relationships

The database uses various types of relationships:

- **One-to-One**: For example, a user has one admin profile
- **One-to-Many**: For example, a company has many products
- **Many-to-Many**: For example, products belong to many categories, and categories have many products
- **Polymorphic**: For example, addresses can belong to either companies or customers

## Eloquent ORM

The backend uses Laravel's Eloquent ORM to interact with the database. Eloquent provides an ActiveRecord implementation for working with the database, making it easy to:

- Create, read, update, and delete records
- Define relationships between models
- Query the database using a fluent interface
- Use eager loading to avoid N+1 query problems

## Migrations

Database structure is defined using Laravel migrations, which are version-controlled and allow for easy database schema changes. Migrations can be found in the `database/migrations` directory.