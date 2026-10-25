<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GenerateOpenApiDocs extends Command
{
    protected $schemas = [];
    protected $modelProperties = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-open-api-docs {--force : Force regenerate documentation even if it exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate OpenAPI documentation for controllers';

    public function handle()
    {
        $force = $this->option('force');

        if ($force && !$this->confirm('This will override existing OpenAPI documentation. Continue?')) {
            $this->info('Operation cancelled.');
            return;
        }

        // Primero generar los schemas de los modelos
        $this->generateModelSchemas();

        $this->generateSchemasFile();

        // Luego generar la documentación de los controladores
        $this->generateControllerDocs($force);

        $this->info('OpenAPI documentation generated successfully!');
    }

    private function generateModelSchemas(): void
    {
        $modelsPath = app_path('Models');
        $modelFiles = File::files($modelsPath);

        foreach ($modelFiles as $file) {
            $className = str_replace('.php', '', $file->getFilename());
            $fullClassName = "App\\Models\\{$className}";

            if (class_exists($fullClassName)) {
                $this->updateModelWithSchema($file->getPathname(), $fullClassName);
                $this->generateSchemaForModel($fullClassName);
            }
        }
    }

    private function updateModelWithSchema($filePath, $modelClass): void
    {
        try {
            /** @var Model $model */
            $model = new $modelClass;
            $tableName = $model->getTable();
            $modelName = class_basename($modelClass);

            // Get columns information
            $columns = Schema::getConnection()->getSchemaBuilder()->getColumnListing($tableName);
            $properties = [];
            $required = [];

            foreach ($columns as $column) {
                $columnType = Schema::getConnection()->getSchemaBuilder()->getColumnType($tableName, $column);
                $columnDefinition = Schema::getConnection()->getSchemaBuilder()->getColumns($tableName);

                $property = $this->convertColumnToProperty($column, $columnType, $tableName);

                if (!($columnDefinition[$column]['nullable'] ?? true) && !in_array($column, ['created_at', 'updated_at']
                    )) {
                    $required[] = $column;
                }

                $properties[$column] = $property;
            }

            // Read current file content
            $content = File::get($filePath);

            // Generate new schema annotation
            $schemaAnnotation = $this->generateModelSchemaAnnotation($modelName, $properties, $required);

            // Replace or add schema annotation
            $newContent = $this->updateModelContent($content, $schemaAnnotation);

            // Write back to file
            File::put($filePath, $newContent);

            $this->info("Updated schema for model: {$modelName}");
        } catch (\Exception $e) {
            $this->warn("Could not update schema for {$modelClass}: " . $e->getMessage());
        }
    }

    private function generateModelSchemaAnnotation(string $modelName, array $properties, array $required): string
    {
        $propertyAnnotations = [];
        foreach ($properties as $name => $property) {
            $annotation = "#[OA\Property(\n";
            $annotation .= "        property: '{$name}',\n";
            $annotation .= "        type: '{$property['type']}'";

            if (isset($property['format'])) {
                $annotation .= ",\n        format: '{$property['format']}'";
            }
            if (isset($property['nullable']) && $property['nullable']) {
                $annotation .= ",\n        nullable: true";
            }

            $annotation .= "\n    )]";
            $propertyAnnotations[] = $annotation;
        }

        $requiredAnnotation = !empty($required) ?
            ",\n    required: ['" . implode("', '", $required) . "']" :
            '';
        $resourceModelName = ucfirst(strtolower($modelName));
        return <<<SCHEMA
#[OA\Schema(
    schema: '{$resourceModelName}',
    description: '{$modelName} model'{$requiredAnnotation}
)]
SCHEMA;
    }

    private function updateModelContent(string $content, string $schemaAnnotation): string
    {
        // Add OpenApi use statement if not present
        if (!str_contains($content, 'use OpenApi\Attributes as OA;')) {
            $content = preg_replace(
                '/^(namespace.*?;)\s*/s',
                "$1\n\nuse OpenApi\Attributes as OA;\n",
                $content
            );
        }

        // Remove existing schema annotation if present
        $content = preg_replace('/#\[OA\\\\Schema\(.*?\)\]\n/s', '', $content);

        // Add new schema annotation before class declaration
        $content = preg_replace(
            '/(class\s+\w+\s+extends\s+Model\s*(?:implements\s+\w+\s*)?{)/s',
            $schemaAnnotation . "\n$1",
            $content
        );

        return $content;
    }

    private function generateSchemaForModel($modelClass)
    {
        try {
            /** @var Model $model */
            $model = new $modelClass;
            $tableName = $model->getTable();

            // Obtener la información de las columnas usando Schema
            $columns = Schema::getConnection()->getSchemaBuilder()->getColumnListing($tableName);
            $properties = [];
            $required = [];

            foreach ($columns as $column) {
                $columnType = Schema::getConnection()->getSchemaBuilder()->getColumnType($tableName, $column);
                $property = $this->convertColumnToProperty($column, $columnType, $tableName);

                // Determinar si es requerido usando Schema directamente
                $columnDefinition = Schema::getConnection()
                    ->getSchemaBuilder()
                    ->getColumns($tableName);

                if (!($columnDefinition[$column]['nullable'] ?? true) && !in_array($column, ['created_at', 'updated_at']
                    )) {
                    $required[] = $column;
                }

                $properties[$column] = $property;
            }

            // Guardar las propiedades para usar en los schemas
            $modelName = class_basename($modelClass);
            $this->modelProperties[$modelName] = [
                'properties' => $properties,
                'required' => $required
            ];

            // Generar también el schema para el Request
            $this->generateRequestSchema($modelName, $properties, $required);
        } catch (\Exception $e) {
            $this->warn("Could not generate schema for {$modelClass}: " . $e->getMessage());
        }
    }

    private function convertColumnToProperty(string $column, string $columnType, string $tableName): array
    {
        $property = [
            'type' => $this->mapDatabaseTypeToOpenApi($columnType)['type'],
        ];

        // Agregar formato si existe
        $format = $this->mapDatabaseTypeToOpenApi($columnType)['format'] ?? null;
        if ($format) {
            $property['format'] = $format;
        }

        // Verificar si es nullable usando Schema directamente
        $columnDefinition = Schema::getConnection()
            ->getSchemaBuilder()
            ->getColumns($tableName);

        $isNullable = $columnDefinition[$column]['nullable'] ?? true;

        if ($isNullable) {
            $property['nullable'] = true;
        }

        return $property;
    }

    /**
     * @param string $modelName
     * @return string
     */
    protected function getResourceName(string $modelName): string
    {
        $modelResurceName = ucfirst(strtolower($modelName));
        if(Str::endsWith($modelName, 'Request'))
            $modelResurceName = ucfirst(strtolower(Str::beforeLast($modelName, 'Request'))) . "Request";
        return  $modelResurceName;
    }

    private function generateSchemaClass(string $modelName, array $schema): string
    {
        $properties = [];
        foreach ($schema['properties'] as $name => $property) {
            $propertyDef = sprintf(
                '    #[OA\Property(property: "%s", type: "%s"',
                $name,
                $property['type']
            );

            if (isset($property['format'])) {
                $propertyDef .= sprintf(', format: "%s"', $property['format']);
            }
            if (isset($property['nullable']) && $property['nullable']) {
                $propertyDef .= ', nullable: true';
            }
            $propertyDef .= ")]\n";
            $propertyDef .= sprintf('    private $%s;', $name);
            $properties[] = $propertyDef;
        }


        $schemaContent = sprintf(
            "\n    #[OA\Schema(
        schema: '%s',
        description: '%s model'%s
    )]\n    class %s\n    {\n%s\n    }\n",
            $this->getResourceName($modelName),
            $modelName,
            !empty($schema['required']) ? ",\n        required: ['" . implode("', '", $schema['required']) . "']" : '',
            $modelName,
            implode("\n\n", $properties)
        );

        return $schemaContent;
    }

    private function mapDatabaseTypeToOpenApi(string $dbType): array
    {
        $typeMap = [
            'integer' => ['type' => 'integer'],
            'bigint' => ['type' => 'integer', 'format' => 'int64'],
            'smallint' => ['type' => 'integer', 'format' => 'int32'],
            'decimal' => ['type' => 'number', 'format' => 'double'],
            'float' => ['type' => 'number', 'format' => 'float'],
            'string' => ['type' => 'string'],
            'text' => ['type' => 'string'],
            'datetime' => ['type' => 'string', 'format' => 'date-time'],
            'date' => ['type' => 'string', 'format' => 'date'],
            'boolean' => ['type' => 'boolean'],
            'json' => ['type' => 'object'],
            'timestamp' => ['type' => 'string', 'format' => 'date-time'],
            'char' => ['type' => 'string'],
            'varchar' => ['type' => 'string'],
        ];

        return $typeMap[$dbType] ?? ['type' => 'string'];
    }


    private function generateRequestSchema(string $modelName, array $properties, array $required)
    {
        // Eliminar propiedades que no deberían estar en el request
        unset($properties['id']);
        unset($properties['created_at']);
        unset($properties['updated_at']);

        $this->modelProperties["{$modelName}Request"] = [
            'properties' => $properties,
            'required' => array_diff($required, ['id', 'created_at', 'updated_at'])
        ];
    }

    private function generateSchemasFile()
    {
        $content = "<?php\n\nnamespace App\OpenApi;\n\n";
        $content .= "use OpenApi\Attributes as OA;\n\n";

        // Add OpenAPI Info annotation
        $content .= "#[OA\Info(
    version: '1.0.0',
    title: 'API Documentation',
    description: 'API Documentation'
)]\n\n";

        // Add Security Scheme
        $content .= "#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer'
)]\n\n";

        // Ensure the OpenApi directory exists
        if (!File::exists(app_path('OpenApi'))) {
            File::makeDirectory(app_path('OpenApi'));
            File::makeDirectory(app_path('OpenApi/Schemas'));
        }

        // Generate schema definitions
        foreach ($this->modelProperties as $modelName => $schema) {
            if (!File::exists(app_path('OpenApi/Schemas/'))) {
                File::makeDirectory(app_path('OpenApi/Schemas'));
            }
            $modelSchemaContent = "<?php\n\n";
            $modelSchemaContent .= $this->generateSchemaDefinition($modelName, $schema);
            $modelSchemaContent .= "\n";
            File::put(app_path("OpenApi/Schemas/{$modelName}Schema.php"), $modelSchemaContent);
        }

        File::put(app_path('OpenApi/Schemas.php'), $content);
    }

    private function generateSchemaDefinition(string $modelName, array $schema): string
    {
        $content = "namespace App\OpenApi\Schemas;\n\n";
        $content .= "use OpenApi\Attributes as OA;\n\n";

        $content .= "#[OA\Schema(\n";
        $content .= "    schema: '{$this->getResourceName($modelName)}',\n";
        if (!empty($schema['required'])) {
            $content .= "    required: ['" . implode("', '", $schema['required']) . "'],\n";
        }
        $content .= "    properties: [\n";

        foreach ($schema['properties'] as $name => $property) {
            $content .= "        new OA\Property(\n";
            $content .= "            property: '{$name}',\n";
            $content .= "            type: '{$property['type']}'";

            if (isset($property['format'])) {
                $content .= ",\n            format: '{$property['format']}'";
            }
            if (isset($property['nullable']) && $property['nullable']) {
                $content .= ",\n            nullable: true";
            }

            $content .= "\n        ),\n";
        }

        $content .= "    ]\n";
        $content .= ")]\n";
        $content .= "class {$modelName}Schema {}\n";

        return $content;
    }


    private function generateGenericProperties()
    {
        return "type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time')
        ]";
    }

    private function generateGenericCollectionProperties()
    {
        return "properties: [
            new OA\Property(
                property: 'data',
                type: 'array',
                items: new OA\Items(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time')
                    ]
                )
            ),
            " . $this->generatePaginationProperties() . "
        ]";
    }

    private function generatePaginationProperties()
    {
        return "new OA\Property(
                property: 'meta',
                type: 'object',
                properties: [
                    new OA\Property(property: 'current_page', type: 'integer'),
                    new OA\Property(property: 'from', type: 'integer'),
                    new OA\Property(property: 'last_page', type: 'integer'),
                    new OA\Property(property: 'per_page', type: 'integer'),
                    new OA\Property(property: 'to', type: 'integer'),
                    new OA\Property(property: 'total', type: 'integer')
                ]
            ),
            new OA\Property(
                property: 'links',
                type: 'object',
                properties: [
                    new OA\Property(property: 'first', type: 'string'),
                    new OA\Property(property: 'last', type: 'string'),
                    new OA\Property(property: 'prev', type: 'string', nullable: true),
                    new OA\Property(property: 'next', type: 'string', nullable: true)
                ]
            )";
    }

    private function generateSchemaAnnotation(string $modelName, array $schema): string
    {
        $properties = [];
        foreach ($schema['properties'] as $name => $property) {
            $propertyAnnotation = " *             @OA\\Property(\n";
            $propertyAnnotation .= " *                 property=\"{$name}\",\n";
            $propertyAnnotation .= " *                 type=\"{$property['type']}\"";

            if (isset($property['format'])) {
                $propertyAnnotation .= ",\n *                 format=\"{$property['format']}\"";
            }
            if (isset($property['nullable']) && $property['nullable']) {
                $propertyAnnotation .= ",\n *                 nullable=true";
            }

            $propertyAnnotation .= "\n *             )";
            $properties[] = $propertyAnnotation;
        }

        $requiredString = empty($schema['required']) ? '' :
            " *     required={\n" .
            implode(",\n", array_map(fn($prop) => " *         \"{$prop}\"", $schema['required'])) .
            "\n *     },\n";

        return <<<SCHEMA
/**
 * @OA\\Schema(
 *     schema="{$modelName}",
{$requiredString}
 *     properties={
{$this->indent(implode(",\n", $properties), 1)}
 *     }
 * )
 */
SCHEMA;
    }

    private function indent(string $content, int $levels): string
    {
        return implode(
            "\n",
            array_map(
                fn($line) => str_repeat(" ", $levels * 4) . $line,
                explode("\n", $content)
            )
        );
    }

    function generateControllerDocs($force = false)
    {
        // Obtener todas las rutas definidas
        $routes = Route::getRoutes();
        $routeMap = [];

        // Mapear las rutas por controlador y método
        foreach ($routes as $route) {
            if (is_string($route->action['uses'])) {
                list($controller, $method) = explode('@', $route->action['uses']);

                if (!isset($routeMap[$controller])) {
                    $routeMap[$controller] = [];
                }

                $routeMap[$controller][$method] = [
                    'method' => $route->methods()[0],
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                    'parameters' => $route->parameterNames(),
                ];
            }
        }

        // Directorio de controladores
        $controllersPath = base_path('app/Http/Controllers');
        $files = File::files($controllersPath);

        foreach ($files as $file) {
            $content = File::get($file);
            $className = str_replace('.php', '', $file->getFilename());
            $fullyQualifiedClassName = "App\\Http\\Controllers\\{$className}";

            if (isset($routeMap[$fullyQualifiedClassName])) {
                $methods = $routeMap[$fullyQualifiedClassName];
                $newContent = $this->generateControllerDocsContent($content, $methods, $force);
                File::put($file->getPathname(), $newContent);
            }
        }
    }

    function generateControllerDocsContent($content, $methods, $force = false)
    {
        $lines = explode("\n", $content);
        $newLines = [];
        $classStarted = false;
        $lastMethodEnd = 0;

        foreach ($lines as $index => $line) {
            if (!$classStarted && preg_match('/class\s+\w+/', $line)) {
                $classStarted = true;
                $lastMethodEnd = $index;
            }

            if (trim($line) === '}') {
                $lastMethodEnd = $index;
            }

            if (preg_match('/public function (\w+)\(/', $line, $matches)) {
                $methodName = $matches[1];

                if (isset($methods[$methodName])) {
                    $relevantLines = [];
                    $checkIndex = $index - 1;
                    $hasDocumentation = false;
                    $docStartIndex = null;
                    $existingSummary = null;

                    while ($checkIndex > $lastMethodEnd) {
                        $checkLine = trim($lines[$checkIndex]);
                        if (!empty($checkLine) && !preg_match('/^\s*\/\/|^\s*\*|^\s*\/\*/', $checkLine)) {
                            if (preg_match('/^\s*(protected|private|public|var)\s+/', $checkLine) ||
                                preg_match('/class\s+\w+/', $checkLine)) {
                                break;
                            }
                        }

                        // Buscar y extraer el summary existente
                        if (preg_match('/summary:\s*"([^"]+)"/', $checkLine, $summaryMatch)) {
                            $existingSummary = $summaryMatch[1];
                        }

                        if (str_contains($checkLine, '#[OA\\')) {
                            $hasDocumentation = true;
                            $docStartIndex = $checkIndex;
                        }
                        $relevantLines[] = $lines[$checkIndex];
                        $checkIndex--;
                    }

                    if (!$hasDocumentation || $force) {
                        if ($hasDocumentation && $force) {
                            while ($docStartIndex < $index) {
                                array_pop($newLines);
                                $docStartIndex++;
                            }
                        }

                        $route = $methods[$methodName];
                        $docBlock = $this->generateMethodDocBlock($route, $hasDocumentation, $existingSummary);
                        $newLines[] = $docBlock;
                    }
                }
            }

            $newLines[] = $line;
        }

        $useStatement = "use OpenApi as OA;\nuse Symfony\Component\HttpFoundation\Response;";
        $content = implode("\n", $newLines);
        if (!str_contains($content, 'use OpenApi')) {
            $content = str_replace("<?php\n", "<?php\n\n{$useStatement}\n", $content);
        }

        return $content;
    }

    private function generateResponseContent($route)
    {
        $resourceName = ucfirst($this->extractResourceName($route['uri']));
        $method = strtolower($route['method']);

        // Verificar si existe el schema para este recurso
        $hasSchema = isset($this->modelProperties[$resourceName]);

        switch ($method) {
            case 'get':
                if (strpos($route['uri'], '{') !== false) {
                    return "properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/{$resourceName}'
                    )
                ]";
                } else {
                    return "properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/{$resourceName}')
                    ),
                    " . $this->generatePaginationProperties() . "
                ]";
                }
            case 'post':
            case 'put':
                return "properties: [
                new OA\Property(
                    property: 'data',
                    ref: '#/components/schemas/{$resourceName}'
                ),
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Resource " . ($method === 'post' ? 'created' : 'updated') . " successfully'
                )
            ]";
            case 'delete':
                return "properties: [
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Resource deleted successfully'
                )
            ]";
            default:
                return "type: 'object'";
        }
    }

    private function generateRequestBody($route)
    {
        $method = strtolower($route['method']);
        if (!in_array($method, ['post', 'put'])) {
            return '';
        }

        $resourceName = ucfirst($this->extractResourceName($route['uri']));
        return <<<BODY
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/{$resourceName}Request'
            )
        ),
BODY;
    }

    private function generateGenericRequestProperties()
    {
        return "
            type: 'object',
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'description', type: 'string', nullable: true)
            ]
        ";
    }

    private function generateParametersBlock($parameters, $route): string
    {
        if (empty($parameters)) {
            return "\n";
        }

        $paramBlock = "\n        parameters: [";
        foreach ($parameters as $param) {
            $description = $this->generateParameterDescription($param, $route);
            $paramBlock .= <<<PARAM

            new OA\Parameter(
                name: "{$param}",
                description: "{$description}",
                in: "path",
                required: true
            ),
PARAM;
        }
        $paramBlock .= "\n        ],";

        return $paramBlock;
    }

    private function generateActionSummary($uri, $method)
    {
        $resource = $this->extractResourceName($uri);
        $resourceName = ucfirst($resource);

        switch ($method) {
            case 'Get':
                return strpos($uri, '{') !== false
                    ? "Display the specified {$resourceName}."
                    : "Display a listing of {$resourceName}s.";
            case 'Post':
                return "Create a new {$resourceName}.";
            case 'Put':
                return "Update the specified {$resourceName}.";
            case 'Delete':
                return "Remove the specified {$resourceName}.";
            default:
                return "Manage the {$resourceName}.";
        }
    }

    private function generateParameterDescription($param, $route)
    {
        $resourceName = ucfirst($this->extractResourceName($route['uri']));
        $method = strtolower($route['method']);

        switch ($method) {
            case 'get':
                return "The {$resourceName} identifier to retrieve.";
            case 'put':
                return "The {$resourceName} identifier to update.";
            case 'delete':
                return "The {$resourceName} identifier to remove.";
            default:
                return "The {$resourceName} identifier.";
        }
    }

    private function generateTag($uri)
    {
        $resource = $this->extractResourceName($uri);
        return ucfirst($resource);
    }

    private function extractResourceName($uri)
    {
        // Eliminar la versión de API y parámetros de la URI
        $uri = preg_replace('/^api\/v\d+\//', '', $uri);
        $parts = explode('/', $uri);

        // Obtener el segmento base (sin parámetros)
        $resource = '';
        foreach ($parts as $part) {
            if (!str_contains($part, '{')) {
                $resource = $part;
            }
        }

        // Singularizar el nombre del recurso
        $resource = Str::singular($resource);

        // Convertir guiones y guiones bajos a espacios y capitalizar
        return str_replace(['-', '_'], ' ', $resource);
    }

    private function generateMethodDocBlock($route, $hasDocumentation = false, $existingSummary = null)
    {
        $method = ucfirst(strtolower($route['method']));
        $parameters = $this->generateParametersBlock($route['parameters'], $route);
        $responseContent = $this->generateResponseContent($route);

        // Usar el summary existente o generar uno nuevo basado en la acción
        $summary = $hasDocumentation && $existingSummary
            ? $existingSummary
            : $this->generateActionSummary($route['uri'], $method);

        $tag = $this->generateTag($route['uri']);

        // Agregar requestBody para métodos POST y PUT
        $requestBody = $this->generateRequestBody($route);

        return <<<DOCBLOCK
    #[OA\\{$method}(
        path: "{$route['uri']}",
        summary: "{$summary}",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        {$requestBody}
        tags: ["{$tag}"],{$parameters}
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    {$responseContent}
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
DOCBLOCK;
    }
}
