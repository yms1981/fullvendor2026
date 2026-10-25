# Installation and Setup

This guide will help you set up the Laravel backend for development using Laravel Sail, which provides a Docker-based development environment.

## Prerequisites

Before you begin, ensure you have the following installed:

- Docker Desktop (for Windows/Mac) or Docker Engine + Docker Compose (for Linux)
- Git
- Composer (for initial Sail installation only)

Laravel Sail provides a pre-configured Docker environment, so you don't need to install PHP, MySQL, or other dependencies directly on your machine.

## Installation Steps with Laravel Sail

### 1. Clone the Repository

If you haven't already cloned the repository:

```bash
git clone <repository-url>
cd fullvendor
```

### 2. Navigate to the API Directory

```bash
cd api
```

### 3. Install Composer Dependencies

Since Laravel Sail is already included in the project's composer.json, you can use a Docker container to install Composer dependencies without requiring PHP installed on your local machine:

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php82-composer:latest \
    composer install
```

### 4. Environment Configuration

Copy the example environment file and configure it:

```bash
cp .env.example .env
```

The Docker Compose file is already configured in the project, so you don't need to run the sail:install command.

The default database configuration in `.env` should work with Sail, but you can adjust it if needed:

```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

### 5. Start Sail

Start the Docker containers using Sail:

```bash
./vendor/bin/sail up -d
```

For convenience, you might want to create an alias for the Sail command:

```bash
alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'
```

Then you can simply use:

```bash
sail up -d
```

### 6. Sincronizar la base de datos

Genera la clave si aún no lo hiciste:

```bash
sail artisan key:generate
```

El comando `php artisan migrate` **ya no ejecuta** `database/migrations/` (esquema antiguo). Redirige a `schema:sync`, que aplica el esquema desde el repositorio:

- `database/sql/appfv_full_schema.sql` — tablas, columnas e índices
- `database/sql/views/` — vistas
- `database/sql/seed_data/` — datos fijos (countries, states, cities, etc.)
- Admin de plataforma `/admin` si falta

```bash
# Ver qué cambiaría (recomendado la primera vez)
sail artisan schema:sync --dry-run

# Aplicar esquema + datos fijos + vistas + admin
sail artisan migrate --force
# equivalente explícito:
sail artisan schema:sync --force

# Comprobar alineación BD ↔ repo
sail artisan schema:audit
```

Opcional (solo si necesitas seeders Laravel concretos, no sustituyen seed_data):

```bash
sail artisan db:seed
```

```bash
# Enlace de storage
sail artisan storage:link
```

The API will be available at `http://localhost`.

## Using Sail for Daily Development

Sail provides commands for common development tasks:

```bash
# Start the containers
sail up -d

# Stop the containers
sail down

# Run PHP commands
sail php --version

# Run Composer commands
sail composer require package-name

# Run Artisan commands
sail artisan list

# Run NPM commands
sail npm install

# Access the MySQL CLI
sail mysql

# Run a shell within the Laravel container
sail shell

# Run Tinker
sail tinker
```

## Testing

To run the tests with Sail:

```bash
sail artisan test
```

Or run a specific test:

```bash
sail artisan test --filter=UserTest
```

## Common Issues

### Permission Issues

If you encounter permission issues with storage or cache:

```bash
sail exec -u root chmod -R 775 storage bootstrap/cache
```

### Database Connection Issues

If you have issues connecting to the database:

1. Ensure the Sail containers are running:
   ```bash
   sail ps
   ```

2. Check the database credentials in the `.env` file match the Sail defaults:
   ```
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=laravel
   DB_USERNAME=sail
   DB_PASSWORD=password
   ```

3. Try restarting the containers:
   ```bash
   sail down
   sail up -d
   ```

### Composer Memory Limit

If Composer runs out of memory during installation:

```bash
sail composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
```

### Docker Issues

If you encounter Docker-related issues:

1. Ensure Docker Desktop is running (on Windows/Mac)
2. Try restarting Docker
3. Check for port conflicts with other applications
4. Increase Docker's allocated memory in Docker Desktop settings
