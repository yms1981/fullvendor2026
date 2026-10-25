# Testing

This document provides an overview of the testing approach and tools used in the backend.

## Quick start (monorepo)

Desde la raíz del repo:

```bash
make help
make test              # unitarios API + MVC
make test-api          # PHPUnit Unit + Feature (API Laravel)
make test-mvc          # PHPUnit MVC (CodeIgniter)
make test-dusk         # Selenium / Laravel Dusk (browser)
```

Requisitos Docker: contenedores `laravel.test`, `fullvendor`, `mariadb` y `selenium` (ya definidos en `api/docker-compose.yml`).

### Base de datos `testing` (PHPUnit API)

Los tests Unit/Feature de la API usan `DB_DATABASE=testing` (`phpunit.xml`). Crear la BD si no existe:

```bash
docker exec fullvendor-1_1x-mariadb-1 mariadb -u root -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS testing; GRANT ALL ON testing.* TO 'appfv'@'%';"
```

Luego sincronizar esquema (mismo flujo que desarrollo):

```bash
docker exec fullvendor-1_1x-laravel.test-1 bash -lc 'cd /var/www/html && DB_DATABASE=testing php artisan schema:sync --force'
```

### Selenium / Dusk (browser)

1. El servicio `selenium` (`selenium/standalone-chrome`) ya está en el compose y en la red `sail`.
2. Dependencia: `laravel/dusk` en `api/`.
3. Config: `api/phpunit.dusk.xml` apunta el browser a `http://fullvendor` (MVC) y el hub a `http://selenium:4444/wd/hub`.
4. Copia el env de ejemplo (una vez):

```bash
cp api/.env.dusk.example api/.env.dusk.local
# Pega el mismo APP_KEY que api/.env
```

5. Ejecutar:

```bash
make test-dusk
# o:
docker exec fullvendor-1_1x-laravel.test-1 bash -lc 'cd /var/www/html && php artisan dusk'
```

Los tests viven en `api/tests/Browser/`. Capturas ante fallo: `api/tests/Browser/screenshots/`.

## Testing Framework

The backend uses PHPUnit as the testing framework, which is the standard testing framework for PHP applications. Laravel Dusk + Selenium cover browser (E2E) tests against the MVC UI.

## Test Types

The backend includes several types of tests:

### Unit Tests

Unit tests focus on testing individual components in isolation. These tests are located in the `tests/Unit` directory.

Examples of unit tests include:
- Testing model methods
- Testing service classes
- Testing helper functions

### Feature Tests

Feature tests focus on testing the application as a whole, including HTTP requests and responses. These tests are located in the `tests/Feature` directory.

Examples of feature tests include:
- Testing API endpoints
- Testing authentication
- Testing authorization

### Integration Tests

Integration tests focus on testing the interaction between different components. These tests are often included in the feature tests.

Examples of integration tests include:
- Testing database interactions
- Testing external service integrations

## Test Database

Tests use a separate database to avoid affecting the development or production database. This is configured in the `phpunit.xml` file:

```xml
<php>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
</php>
```

## Running Tests

To run all tests:

```bash
php artisan test
```

To run a specific test file:

```bash
php artisan test --filter=UserTest
```

To run a specific test method:

```bash
php artisan test --filter=UserTest::testCreateUser
```

## Test Structure

### Test Case Classes

Test case classes extend either `Tests\TestCase`, `Tests\Feature\TestCase`, or `Tests\Unit\TestCase`:

```php
namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;

class UserControllerTest extends TestCase
{
    // Test methods
}
```

### Test Methods

Test methods start with the word "test" or are annotated with `@test`:

```php
public function testUserCanBeCreated()
{
    // Test code
}

/**
 * @test
 */
public function user_can_be_updated()
{
    // Test code
}
```

## Test Helpers

### Factories

Factories are used to create test data:

```php
$user = User::factory()->create();
```

### Database Transactions

Tests use database transactions to roll back changes after each test:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;
    
    // Test methods
}
```

### Authentication

Tests can authenticate as a user:

```php
$user = User::factory()->create();
$response = $this->actingAs($user)->get('/api/v2/user');
```

### HTTP Testing

Tests can make HTTP requests to the application:

```php
$response = $this->get('/api/v2/users');
$response->assertStatus(200);
```

## Test Coverage

Test coverage reports can be generated to see how much of the code is covered by tests:

```bash
php artisan test --coverage
```

## Continuous Integration

Tests are run automatically in the CI/CD pipeline to ensure that changes don't break existing functionality.

## Example Test

Here's an example of a feature test for the UserController:

```php
namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testUserCanBeCreated()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->postJson('/api/v2/users', $userData);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', $userData['name'])
            ->assertJsonPath('data.email', $userData['email']);

        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);
    }
}
```

## Best Practices

- Write tests for all new features
- Run tests before committing changes
- Keep tests fast and independent
- Use descriptive test method names
- Test edge cases and error conditions
- Use factories to create test data
- Use database transactions to roll back changes