<?php

namespace App\Models;

use App\Http\Requests\StoreImageRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use League\Flysystem\UnableToRetrieveMetadata;

/**
 *
 *
 * @property int $id
 * @property string $name
 * @property string $path
 * @property string $type
 * @property string $size
 * @property string $extension
 * @property string $mime_type
 * @property string $original_name
 * @property int|null $company_id
 * @property string|null $language
 * @property string $disk
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\ImageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereExtension($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereUpdatedAt($value)
 * @property string $uuid
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereUuid($value)
 * @mixin \Eloquent
 */
class Image extends Model
{
    /** @use HasFactory<\Database\Factories\ImageFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'uuid';

    public static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }


    /**
     * Disco configurado para nuevas fotos (IMAGES_DISK).
     * Aliases: local|directory|dir|filesystem → images_local
     */
    public static function defaultDisk(): string
    {
        $configured = strtolower(trim((string) config('filesystems.images', env('IMAGES_DISK', 'minio'))));

        return match ($configured) {
            'local', 'directory', 'dir', 'filesystem', 'images_local' => 'images_local',
            's3' => 's3',
            'cloud' => (string) config('filesystems.cloud', 'minio'),
            default => $configured !== '' ? $configured : 'minio',
        };
    }

    public static function usesDirectoryLayout(?string $disk = null): bool
    {
        $disk = $disk ?: self::defaultDisk();

        return in_array($disk, ['images_local', 'legacy'], true);
    }

    /**
     * Imagen servible con la arquitectura activa (IMAGES_DISK): disco alineado y path válido.
     */
    public static function isOnActiveStorage(?self $image): bool
    {
        if ($image === null) {
            return false;
        }

        $path = trim((string) ($image->path ?? ''));
        if ($path === '' || stripos($path, 'no-image') !== false) {
            return false;
        }

        $disk = (string) ($image->disk ?? '');
        $activeDisk = self::defaultDisk();

        if (self::usesDirectoryLayout($activeDisk)) {
            return in_array($disk, ['images_local', 'legacy'], true);
        }

        return in_array($disk, ['minio', 's3'], true);
    }

    /**
     * Scope: registros en `images` servibles con IMAGES_DISK actual.
     */
    public function scopeOnActiveStorage($query, ?string $activeDisk = null)
    {
        $activeDisk = $activeDisk ?? self::defaultDisk();

        $query->whereNotNull('path')
            ->where('path', '!=', '')
            ->where('path', 'not like', '%no-image%');

        if (self::usesDirectoryLayout($activeDisk)) {
            return $query->whereIn('disk', ['images_local', 'legacy']);
        }

        return $query->whereIn('disk', ['minio', 's3']);
    }

    public function getStorage(): \Illuminate\Contracts\Filesystem\Filesystem|\Illuminate\Filesystem\FilesystemAdapter|\Illuminate\Contracts\Filesystem\Cloud
    {
        return Storage::disk($this->resolveActiveDisk());
    }

    /**
     * Disco efectivo según IMAGES_DISK (ignora mezclas: local = solo disco, minio = solo object storage).
     */
    public function resolveActiveDisk(): string
    {
        $preferred = self::defaultDisk();

        if (self::usesDirectoryLayout($preferred)) {
            return 'images_local';
        }

        if (in_array((string) $this->disk, ['minio', 's3'], true)) {
            return (string) $this->disk;
        }

        return $preferred !== '' ? $preferred : 'minio';
    }

    /**
     * Path relativo dentro del disco según arquitectura.
     * MinIO/S3: {companyId}/{name}
     * Directorio: {dir}/{companyId}/{name}  (products|categories|…)
     */
    public function buildStoragePath($companyId = null, string $type = 'product'): string
    {
        if (!isset($companyId)) {
            $companyId = $this->company_id;
        }

        $type = strtolower(trim($type));
        if (! in_array($type, ['product', 'category', 'company', 'user', 'chat', 'catalog'], true)) {
            $type = 'product';
        }

        if (self::usesDirectoryLayout($this->disk ?: self::defaultDisk())) {
            $dir = self::directoryNameForType($type);

            // Avatares de usuario: users/{archivo} (sin company_id), para servirlos
            // como /uploads/users/{archivo} y en chat. Productos/categorías sí van por company.
            if ($type === 'user') {
                return $dir.'/'.$this->name;
            }

            return $companyId
                ? $dir.'/'.$companyId.'/'.$this->name
                : $dir.'/'.$this->name;
        }

        return $companyId
            ? $companyId.'/'.$this->name
            : $this->name;
    }

    /**
     * Carpeta bajo IMAGES_LOCAL_ROOT para cada tipo lógico.
     * product → products, category → categories (plural, layout documentado).
     */
    public static function directoryNameForType(string $type): string
    {
        $type = strtolower(trim($type));

        return match ($type) {
            'category' => 'categories',
            'product' => 'products',
            'company' => 'company',
            'user' => 'users',
            'chat' => 'chat',
            'catalog' => 'catalogs',
            default => 'products',
        };
    }

    /**
     * Extensión preferida de almacenamiento (JPEG tras normalizar; configurable).
     */
    public const STORAGE_EXTENSION = 'jpg';

    public const STORAGE_MIME = 'image/jpeg';

    /** Extensiones de imagen aceptadas al subir / detectar metadatos. */
    public const ACCEPTED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'avif', 'tif', 'tiff', 'heic', 'heif', 'svg', 'ico',
    ];

    public function putContent($imageFile, $companyId = null, $type = 'product')
    {
        if (!isset($companyId)) {
            $companyId = $this->company_id;
        }

        // Escritura siempre según IMAGES_DISK (no mezclar con el valor viejo en BD)
        $this->disk = self::defaultDisk();

        $type = strtolower(trim((string) $type));
        if (! in_array($type, ['product', 'category', 'company', 'user', 'chat', 'catalog'], true)) {
            $type = 'product';
        }

        $bytes = $this->extractUploadBytes($imageFile);
        $payload = $bytes ?? $imageFile;

        // Siempre normalizar: formato compacto + límite de resolución (evita subidas enormes).
        $normalized = $this->normalizeUpload($payload, $type);
        if ($normalized !== null) {
            $payload = $normalized;
            $this->extension = self::storageExtension();
            $this->mime_type = self::storageMime();
            $this->type = self::storageMime();
            $baseName = pathinfo((string) $this->name, PATHINFO_FILENAME);
            if ($baseName === '' || $baseName === '.' || $baseName === false) {
                $baseName = (string) Str::uuid();
            }
            $baseName = self::sanitizeStorageBaseName((string) $baseName);
            $this->name = $baseName . '.' . self::storageExtension();
        } else {
            if (config('images.require_normalization', true)) {
                throw new \RuntimeException(
                    'No se pudo normalizar la imagen. Suba un archivo de imagen válido (jpg, png, webp, gif, etc.).'
                );
            }
            $meta = $this->detectImageMeta(
                is_string($bytes) ? $bytes : null,
                $this->name,
                $this->original_name ?? null
            );
            if ($meta !== null) {
                $this->extension = $meta['extension'];
                $this->mime_type = $meta['mime'];
                $this->type = $meta['mime'];
                $baseName = pathinfo((string) $this->name, PATHINFO_FILENAME);
                if ($baseName === '' || $baseName === '.' || $baseName === false) {
                    $baseName = (string) Str::uuid();
                }
                $baseName = self::sanitizeStorageBaseName((string) $baseName);
                $this->name = $baseName . '.' . $meta['extension'];
            }
            \Log::warning('Image::putContent - no se pudo normalizar; se guarda el binario original', [
                'name' => $this->name,
                'extension' => $this->extension,
                'mime_type' => $this->mime_type,
                'company_id' => $companyId,
            ]);
        }

        $imageFile = $payload;
        $storage = $this->getStorage();
        $filePath = $this->buildStoragePath($companyId, (string) $type);

        \Log::info('Image::putContent - guardando imagen', [
            'disk' => $this->disk,
            'path' => $filePath,
            'company_id' => $companyId,
            'type' => $type,
        ]);

        try {
            if (self::usesDirectoryLayout($this->disk)) {
                $directory = dirname($filePath);
                if ($directory !== '.' && $directory !== '') {
                    $storage->makeDirectory($directory);
                }
            }

            $result = $storage->put($filePath, $imageFile);

            if (!$result) {
                throw new \Exception("No se pudo guardar el archivo en el disco {$this->disk}");
            }

            if (!$storage->exists($filePath)) {
                throw new \Exception("El archivo no se encontró en {$this->disk} después de guardar: {$filePath}");
            }
        } catch (\Exception $e) {
            \Log::error('Image::putContent - Error al guardar imagen: '.$e->getMessage(), [
                'disk' => $this->disk,
                'path' => $filePath,
            ]);
            throw $e;
        }

        $this->path = $filePath;
        try {
            $this->size = $storage->size($filePath);
        } catch (UnableToRetrieveMetadata $e) {
            \Log::warning('Image::putContent - No se pudo obtener el tamaño del archivo: '.$e->getMessage());
            $this->size = is_string($payload) ? strlen($payload) : 0;
        }

        $this->save();
        return $this;
    }

    public static function storageExtension(): string
    {
        $ext = strtolower((string) config('images.storage_extension', self::STORAGE_EXTENSION));
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        return $ext !== '' ? $ext : self::STORAGE_EXTENSION;
    }

    public static function storageMime(): string
    {
        $mime = strtolower((string) config('images.storage_mime', self::STORAGE_MIME));

        return $mime !== '' ? $mime : self::STORAGE_MIME;
    }

    public static function storageQuality(): int
    {
        $q = (int) config('images.storage_quality', 82);

        return max(40, min(95, $q > 0 ? $q : 82));
    }

    /**
     * Nombre de archivo seguro (sin espacios ni caracteres raros) para el disco local.
     */
    public static function sanitizeStorageBaseName(string $baseName): string
    {
        $baseName = trim($baseName);
        $baseName = preg_replace('/\s+/u', '-', $baseName) ?? $baseName;
        $baseName = preg_replace('/[^A-Za-z0-9._-]+/u', '-', $baseName) ?? $baseName;
        $baseName = trim($baseName, '.-_');
        if ($baseName === '') {
            $baseName = (string) Str::uuid();
        }

        return $baseName;
    }

    /**
     * Normaliza un binario de imagen (escala + formato compacto) sin persistir.
     * Usado por putContent y por adjuntos de chat u otros flujos.
     *
     * @return array{bytes: string, extension: string, mime: string}|null
     */
    public static function normalizeBinary(mixed $imageFile, string $type = 'product'): ?array
    {
        $instance = new self();
        $bytes = $instance->normalizeUpload($imageFile, $type);
        if ($bytes === null || $bytes === '') {
            return null;
        }

        return [
            'bytes' => $bytes,
            'extension' => self::storageExtension(),
            'mime' => self::storageMime(),
        ];
    }

    /**
     * Convierte al formato de almacenamiento (JPEG por defecto) y reduce resolución.
     *
     * @param  mixed  $imageFile  string binario|UploadedFile|resource
     */
    private function normalizeUpload(mixed $imageFile, string $type = 'product'): ?string
    {
        try {
            $bytes = is_string($imageFile) ? $imageFile : $this->extractUploadBytes($imageFile);
            if ($bytes === null || $bytes === '') {
                return null;
            }

            [$maxWidth, $maxHeight] = $this->maxDimensionsForType($type);
            $quality = self::storageQuality();
            $targetExt = self::storageExtension();

            $encoded = null;
            foreach ([
                \Intervention\Image\Drivers\Imagick\Driver::class,
                \Intervention\Image\Drivers\Gd\Driver::class,
            ] as $driverClass) {
                if (! class_exists($driverClass)) {
                    continue;
                }
                try {
                    $manager = new ImageManager(new $driverClass());
                    $img = $manager->read($bytes);

                    // Solo reduce; no agranda. Conserva proporción.
                    if ($maxWidth > 0 || $maxHeight > 0) {
                        $img->scaleDown(
                            width: $maxWidth > 0 ? $maxWidth : null,
                            height: $maxHeight > 0 ? $maxHeight : null
                        );
                    }

                    $encoded = $this->encodeNormalizedImage($img, $targetExt, $quality);
                    if ($encoded !== null && $encoded !== '') {
                        \Log::info('Image::normalizeUpload OK', [
                            'type' => $type,
                            'format' => $targetExt,
                            'quality' => $quality,
                            'max_width' => $maxWidth,
                            'max_height' => $maxHeight,
                            'bytes_in' => strlen($bytes),
                            'bytes_out' => strlen($encoded),
                            'driver' => $driverClass,
                        ]);
                        break;
                    }
                } catch (\Throwable $e) {
                    \Log::debug('Image::normalizeUpload driver failed: '.$driverClass.' — '.$e->getMessage());
                    $encoded = null;
                }
            }

            return ($encoded !== null && $encoded !== '') ? $encoded : null;
        } catch (\Throwable $e) {
            \Log::warning('Image::normalizeUpload: '.$e->getMessage());

            return null;
        }
    }

    /**
     * @param  \Intervention\Image\Interfaces\ImageInterface  $img
     */
    private function encodeNormalizedImage($img, string $targetExt, int $quality): ?string
    {
        $targetExt = strtolower($targetExt);
        if ($targetExt === 'jpeg') {
            $targetExt = 'jpg';
        }

        try {
            $encoded = match ($targetExt) {
                'jpg' => (string) $img->toJpeg($quality),
                'webp' => (string) $img->toWebp($quality),
                'png' => (string) $img->toPng(),
                default => (string) $img->toJpeg($quality),
            };

            return $encoded !== '' ? $encoded : null;
        } catch (\Throwable $e) {
            \Log::debug('Image::encodeNormalizedImage failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Compat: nombre antiguo usado en logs/tests.
     *
     * @param  mixed  $imageFile
     */
    private function normalizeUploadToPng(mixed $imageFile, string $type = 'product'): ?string
    {
        return $this->normalizeUpload($imageFile, $type);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function maxDimensionsForType(string $type): array
    {
        $byType = config('images.max_by_type.'.$type);
        $width = is_array($byType) ? (int) ($byType['width'] ?? 0) : 0;
        $height = is_array($byType) ? (int) ($byType['height'] ?? 0) : 0;

        if ($width < 1) {
            $width = (int) config('images.max_width', 1600);
        }
        if ($height < 1) {
            $height = (int) config('images.max_height', 1600);
        }

        return [$width, $height];
    }

    /**
     * Detecta extensión y mime a partir del binario y/o nombres de archivo.
     *
     * @return array{extension: string, mime: string}|null
     */
    private function detectImageMeta(?string $bytes, ?string ...$names): ?array
    {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/pjpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp',
            'image/x-ms-bmp' => 'bmp',
            'image/x-windows-bmp' => 'bmp',
            'image/avif' => 'avif',
            'image/tiff' => 'tiff',
            'image/tif' => 'tif',
            'image/svg+xml' => 'svg',
            'image/heic' => 'heic',
            'image/heif' => 'heif',
            'image/x-icon' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
        ];

        $mime = null;
        if (is_string($bytes) && $bytes !== '' && class_exists(\finfo::class)) {
            try {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $detected = $finfo->buffer($bytes);
                if (is_string($detected) && $detected !== '') {
                    $mime = strtolower($detected);
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $ext = null;
        if ($mime !== null && isset($mimeMap[$mime])) {
            $ext = $mimeMap[$mime];
        }

        if ($ext === null) {
            foreach ($names as $name) {
                $candidate = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
                if ($candidate === 'jpeg') {
                    $candidate = 'jpg';
                }
                if (in_array($candidate, self::ACCEPTED_EXTENSIONS, true)) {
                    $ext = $candidate;
                    break;
                }
            }
        }

        if ($ext === null) {
            return null;
        }

        if ($mime === null || ! str_starts_with($mime, 'image/')) {
            $mime = match ($ext) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'bmp' => 'image/bmp',
                'avif' => 'image/avif',
                'tif', 'tiff' => 'image/tiff',
                'svg' => 'image/svg+xml',
                'heic' => 'image/heic',
                'heif' => 'image/heif',
                'ico' => 'image/x-icon',
                default => 'application/octet-stream',
            };
        }

        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        return ['extension' => $ext, 'mime' => $mime];
    }

    /**
     * @param  mixed  $imageFile
     */
    private function extractUploadBytes(mixed $imageFile): ?string
    {
        if (is_string($imageFile)) {
            return $imageFile;
        }
        if (is_resource($imageFile)) {
            $contents = stream_get_contents($imageFile);
            if (is_string($contents) && $contents !== '') {
                if (stream_get_meta_data($imageFile)['seekable'] ?? false) {
                    rewind($imageFile);
                }

                return $contents;
            }

            return null;
        }
        if (is_object($imageFile) && method_exists($imageFile, 'getContent')) {
            $contents = $imageFile->getContent();

            return is_string($contents) ? $contents : null;
        }
        if (is_object($imageFile) && method_exists($imageFile, 'getRealPath')) {
            $path = $imageFile->getRealPath();
            if (is_string($path) && $path !== '' && is_readable($path)) {
                $contents = file_get_contents($path);

                return is_string($contents) ? $contents : null;
            }
        }

        return null;
    }

    /**
     * Guarda la imagen en el sistema de archivos local en /uploads/products/{company_id} o /uploads/categories/{company_id}
     * 
     * @param mixed $imageFile Contenido del archivo de imagen
     * @param int $companyId ID de la compañía
     * @param string $type Tipo de imagen: 'product' o 'category'
     * @return void
     */
    private function saveToLocalFilesystem($imageFile, $companyId, $type = 'product')
    {
        // Usar la ruta mapeada de Docker: /var/www/mvc_uploads
        // En docker-compose.yml: '../mvc/uploads:/var/www/mvc_uploads'
        $uploadsPath = '/var/www/mvc_uploads';
        
        // Determinar el directorio según el tipo
        $typePath = $type === 'category' ? '/categories' : '/products';
        $baseTypePath = $uploadsPath . $typePath;
        $companyTypePath = $baseTypePath . '/' . $companyId;

        // Log para depuración
        \Log::info("Image::saveToLocalFilesystem - companyId: {$companyId}, type: {$type}, name: {$this->name}");
        \Log::info("Image::saveToLocalFilesystem - uploadsPath: {$uploadsPath}");
        \Log::info("Image::saveToLocalFilesystem - companyTypePath: {$companyTypePath}");

        // Verificar y crear directorios si no existen
        if (!is_dir($uploadsPath)) {
            $result = @mkdir($uploadsPath, 0755, true);
            \Log::info("Image::saveToLocalFilesystem - Creando uploadsPath: " . ($result ? 'success' : 'failed'));
        }
        if (!is_dir($baseTypePath)) {
            $result = @mkdir($baseTypePath, 0755, true);
            \Log::info("Image::saveToLocalFilesystem - Creando baseTypePath: " . ($result ? 'success' : 'failed'));
        }
        if (!is_dir($companyTypePath)) {
            $result = @mkdir($companyTypePath, 0755, true);
            \Log::info("Image::saveToLocalFilesystem - Creando companyTypePath: " . ($result ? 'success' : 'failed'));
        }

        // Guardar el archivo en el sistema de archivos local
        $localFilePath = $companyTypePath . '/' . $this->name;
        \Log::info("Image::saveToLocalFilesystem - localFilePath: {$localFilePath}");
        
        try {
            $content = null;
            if (is_resource($imageFile)) {
                // Si es un recurso, leer su contenido
                $content = stream_get_contents($imageFile);
                // Resetear el puntero del recurso si es necesario
                if (stream_get_meta_data($imageFile)['seekable']) {
                    rewind($imageFile);
                }
            } elseif (is_string($imageFile)) {
                // Si es un string o contenido directo
                $content = $imageFile;
            } else {
                // Si es un objeto con contenido (como UploadedFile)
                $content = is_object($imageFile) && method_exists($imageFile, 'getContent') 
                    ? $imageFile->getContent() 
                    : (string)$imageFile;
            }
            
            if ($content !== null) {
                $bytesWritten = file_put_contents($localFilePath, $content);
                \Log::info("Image::saveToLocalFilesystem - Archivo guardado. Bytes escritos: {$bytesWritten}");
                if ($bytesWritten === false) {
                    \Log::error("Image::saveToLocalFilesystem - Error al escribir el archivo");
                }
            } else {
                \Log::error("Image::saveToLocalFilesystem - No se pudo obtener el contenido de la imagen");
            }
        } catch (\Exception $e) {
            // Log del error pero no fallar la operación principal
            \Log::error("Error al guardar imagen en sistema de archivos local: " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());
        }
    }


    /**
     * Retrieves and processes an image from storage. Optionally allows resizing and format conversion.
     *
     * @param string|null $extension The desired file extension for the returned image (e.g., 'png', 'jpg', 'jpeg', 'webp').
     * @param int|null $width The target width to scale the image to.
     * @param int|null $height The target height to scale the image to.
     * @return ImageInterface The processed image data or the original retrieved image if no processing is applied.
     */
    /**
     * Binario de la imagen según arquitectura (IMAGES_DISK).
     * - images_local / directory → SOLO filesystem (nunca MinIO)
     * - minio (default) → SOLO object storage MinIO/S3 (con fallback legacy local si aplica)
     */
    public function getRawContent(): ?string
    {
        $preferred = self::defaultDisk();
        $paths = $this->candidateStoragePaths();

        if (self::usesDirectoryLayout($preferred)) {
            $disks = array_values(array_unique(array_filter([
                'images_local',
                'legacy',
                $preferred,
                'public',
                'local',
            ])));
        } else {
            // Arquitectura MinIO: leer del disco del registro o minio/s3; no preferir images_local
            $disks = array_values(array_unique(array_filter([
                in_array((string) $this->disk, ['minio', 's3', ''], true) || $this->disk === null
                    ? $preferred
                    : $this->disk,
                $preferred,
                'minio',
                's3',
            ])));
        }

        foreach ($disks as $diskName) {
            try {
                $disk = Storage::disk($diskName);
            } catch (\Throwable $e) {
                continue;
            }
            foreach ($paths as $path) {
                try {
                    if ($disk->exists($path)) {
                        $bytes = $disk->get($path);
                        if (is_string($bytes) && $bytes !== '') {
                            return $bytes;
                        }
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        // Solo en modo directorio: búsqueda directa en uploads legacy
        if (self::usesDirectoryLayout($preferred) && $this->company_id) {
            $local = $this->getImageFromLocalDirectory();
            if (is_string($local) && $local !== '') {
                return $local;
            }
        }

        return null;
    }

    /**
     * Rutas posibles según layout MinIO ({companyId}/{name}) y directorio
     * (products|categories/{companyId}/{name}). Incluye singular legacy (product|category).
     *
     * @return list<string>
     */
    public function candidateStoragePaths(): array
    {
        $paths = [
            $this->path,
            $this->name,
        ];

        if ($this->company_id && $this->name) {
            $paths[] = $this->company_id.'/'.$this->name;
            foreach (['products', 'product', 'categories', 'category', 'company', 'users', 'user', 'chat', 'catalogs', 'catalog'] as $type) {
                $paths[] = $type.'/'.$this->company_id.'/'.$this->name;
            }
            // Avatares planos: users/{name} y legacy user/{name}
            foreach (['users', 'user'] as $userDir) {
                $paths[] = $userDir.'/'.$this->name;
            }
        }

        if ($this->path) {
            $paths[] = basename((string) $this->path);
            if ($this->company_id) {
                $paths[] = $this->company_id.'/'.basename((string) $this->path);
            }
        }

        return array_values(array_unique(array_filter($paths)));
    }

    public function image($extension = null, $width = null, $height = null)
    {
        $image = $this->getRawContent();

        // No sustituir por el placeholder aquí: si no hay archivo real, el cliente
        // (listado / onerror) o ImageController::noImage deben decidir. Sustituir
        // rompe el filtro con/sin foto (todo parece tener imagen).
        if ($image === null || $image === '') {
            throw new \RuntimeException('Image binary not found for uuid '.($this->uuid ?? ''));
        }

        $encoded = null;
        $lastError = null;
        foreach ([
            \Intervention\Image\Drivers\Imagick\Driver::class,
            \Intervention\Image\Drivers\Gd\Driver::class,
        ] as $driverClass) {
            if (! class_exists($driverClass)) {
                continue;
            }
            try {
                $manager = new ImageManager(new $driverClass());
                $img = $manager->read($image);
                if (isset($width)) {
                    $img->scale(width: $width);
                }

                return $img;
            } catch (\Throwable $e) {
                $lastError = $e;
                \Log::debug('Image::image driver failed: '.$driverClass.' — '.$e->getMessage());
            }
        }

        if ($lastError) {
            throw $lastError;
        }

        throw new \RuntimeException('No image driver available');
    }

    /**
     * Intenta leer la imagen desde los directorios locales /uploads/products/{company_id}/ o /uploads/categories/{company_id}/
     * 
     * @return string|null Contenido de la imagen o null si no se encuentra
     */
    private function getImageFromLocalDirectory()
    {
        if (!$this->company_id || !$this->name) {
            return null;
        }

        $uploadsRoots = array_values(array_unique(array_filter([
            env('MVC_UPLOADS_PATH'),
            '/var/www/mvc_uploads',
            dirname(base_path()) . '/mvc/uploads',
            dirname(base_path()) . '/mvc_uploads',
        ])));

        $fileNames = array_values(array_unique(array_filter([
            $this->name,
            $this->original_name,
            basename((string) $this->path),
        ])));

        foreach ($uploadsRoots as $uploadsPath) {
            foreach (['categories', 'category', 'products', 'product'] as $type) {
                foreach ($fileNames as $fileName) {
                    $filePath = rtrim($uploadsPath, '/') . '/' . $type . '/' . $this->company_id . '/' . $fileName;
                    if (file_exists($filePath) && is_readable($filePath)) {
                        return file_get_contents($filePath);
                    }
                }
            }
            foreach (['users', 'user'] as $userDir) {
                foreach ($fileNames as $fileName) {
                    $flat = rtrim($uploadsPath, '/') . '/' . $userDir . '/' . $fileName;
                    if (file_exists($flat) && is_readable($flat)) {
                        return file_get_contents($flat);
                    }
                    $nested = rtrim($uploadsPath, '/') . '/' . $userDir . '/' . $this->company_id . '/' . $fileName;
                    if (file_exists($nested) && is_readable($nested)) {
                        return file_get_contents($nested);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Genera PNG con fondo blanco (o casi blanco) convertido a transparencia.
     * Pensado para fotos de producto en estudio / catálogo.
     */
    public function encodeWithoutWhiteBackground(?int $width = null): string
    {
        $raw = $this->getRawContent();
        if ($raw === null || $raw === '') {
            throw new \RuntimeException('Image binary not found for uuid '.($this->uuid ?? ''));
        }

        if (class_exists(\Imagick::class)) {
            try {
                return $this->encodeWithoutWhiteBackgroundImagick($raw, $width);
            } catch (\Throwable $e) {
                \Log::debug('Image::encodeWithoutWhiteBackground Imagick failed: '.$e->getMessage());
            }
        }

        return $this->encodeWithoutWhiteBackgroundGd($raw, $width);
    }

    private function encodeWithoutWhiteBackgroundImagick(string $raw, ?int $width): string
    {
        $im = new \Imagick();
        $im->readImageBlob($raw);
        if ($im->getNumberImages() > 1) {
            $im = $im->coalesceImages();
            $im->setIteratorIndex(0);
        }
        $im->setImageFormat('png');
        $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);
        if ($width !== null && $width > 0) {
            $im->thumbnailImage($width, $width, true, true);
        }

        $quantum = (float) \Imagick::getQuantum();
        $fuzz = 0.22 * $quantum;
        // 1) Pintar blancos cercanos como transparentes (tolerancia ~22%)
        $im->transparentPaintImage(new \ImagickPixel('white'), 0.0, $fuzz, false);
        // Segunda pasada un poco más agresiva sobre grises muy claros
        $im->transparentPaintImage(new \ImagickPixel('rgb(248,248,248)'), 0.0, 0.12 * $quantum, false);

        // 2) Flood fill desde esquinas si el fondo es blanco/casi blanco (estudios)
        $w = $im->getImageWidth();
        $h = $im->getImageHeight();
        $corners = [[0, 0], [$w - 1, 0], [0, $h - 1], [$w - 1, $h - 1]];
        foreach ($corners as [$x, $y]) {
            if ($x < 0 || $y < 0) {
                continue;
            }
            try {
                $pixel = $im->getImagePixelColor($x, $y);
                $color = $pixel->getColor(true);
                $r = (float) ($color['r'] ?? 0);
                $g = (float) ($color['g'] ?? 0);
                $b = (float) ($color['b'] ?? 0);
                // getColor(true) → 0..1
                if ($r >= 0.92 && $g >= 0.92 && $b >= 0.92) {
                    $im->floodFillPaintImage(
                        new \ImagickPixel('transparent'),
                        $fuzz,
                        $pixel,
                        $x,
                        $y,
                        false
                    );
                }
            } catch (\Throwable $e) {
                // Continuar con el resto de esquinas
            }
        }

        $blob = $im->getImagesBlob();
        $im->clear();
        $im->destroy();

        if (! is_string($blob) || $blob === '') {
            throw new \RuntimeException('Empty Imagick PNG blob');
        }

        return $blob;
    }

    private function encodeWithoutWhiteBackgroundGd(string $raw, ?int $width): string
    {
        $src = @imagecreatefromstring($raw);
        if ($src === false) {
            throw new \RuntimeException('GD could not read image');
        }

        $sw = imagesx($src);
        $sh = imagesy($src);
        $tw = $sw;
        $th = $sh;
        if ($width !== null && $width > 0 && max($sw, $sh) > $width) {
            $scale = $width / max($sw, $sh);
            $tw = max(1, (int) round($sw * $scale));
            $th = max(1, (int) round($sh * $scale));
        }

        $dst = imagecreatetruecolor($tw, $th);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $tw, $th, $transparent);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $sw, $sh);
        imagedestroy($src);

        $threshold = 238; // casi blanco
        for ($y = 0; $y < $th; $y++) {
            for ($x = 0; $x < $tw; $x++) {
                $rgba = imagecolorat($dst, $x, $y);
                $a = ($rgba & 0x7F000000) >> 24;
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                if ($r >= $threshold && $g >= $threshold && $b >= $threshold) {
                    imagesetpixel($dst, $x, $y, $transparent);
                    continue;
                }
                // Suavizar bordes: blancos medios → semi-transparentes
                if ($r >= 220 && $g >= 220 && $b >= 220) {
                    $whiteness = (int) min(127, (int) round((($r + $g + $b) / 3 - 220) / (255 - 220) * 127));
                    $semi = imagecolorallocatealpha($dst, $r, $g, $b, $whiteness);
                    imagesetpixel($dst, $x, $y, $semi);
                } elseif ($a > 0) {
                    // mantener
                }
            }
        }

        ob_start();
        imagepng($dst, null, 6);
        $blob = (string) ob_get_clean();
        imagedestroy($dst);

        if ($blob === '') {
            throw new \RuntimeException('Empty GD PNG blob');
        }

        return $blob;
    }

    public function deleteFromStorage(): void
    {
        $disk = $this->getStorage();
        
        // El path puede estar en $this->path o construirse como {companyId}/{name}
        $pathToDelete = $this->path;
        
        // Si no hay path pero hay company_id y name, construir el path
        if (!$pathToDelete && $this->company_id && $this->name) {
            $pathToDelete = $this->company_id . '/' . $this->name;
        }
        
        \Log::info("Image::deleteFromStorage - Intentando eliminar imagen");
        \Log::info("Image::deleteFromStorage - UUID: " . ($this->uuid ?? 'null'));
        \Log::info("Image::deleteFromStorage - Path a eliminar: " . ($pathToDelete ?? 'null'));
        \Log::info("Image::deleteFromStorage - Company ID: " . ($this->company_id ?? 'null'));
        \Log::info("Image::deleteFromStorage - Name: " . ($this->name ?? 'null'));
        \Log::info("Image::deleteFromStorage - Disk: " . ($this->disk ?? 'null'));
        
        if (!$pathToDelete) {
            \Log::warning("Image::deleteFromStorage - No se pudo determinar el path para eliminar");
            return;
        }
        
        try {
            if ($disk->exists($pathToDelete)) {
                \Log::info("Image::deleteFromStorage - Archivo existe en MinIO, procediendo a eliminar: {$pathToDelete}");
                $result = $disk->delete($pathToDelete);
                \Log::info("Image::deleteFromStorage - Resultado de eliminación: " . ($result ? 'exitoso' : 'fallido'));
                
                if ($result) {
                    \Log::info("Image::deleteFromStorage - ✓ Archivo eliminado exitosamente de MinIO: {$pathToDelete}");
                } else {
                    \Log::error("Image::deleteFromStorage - ✗ Error: No se pudo eliminar el archivo de MinIO: {$pathToDelete}");
                }
            } else {
                \Log::warning("Image::deleteFromStorage - Archivo NO existe en MinIO (puede que ya fue eliminado): {$pathToDelete}");
                // Continuar con la eliminación del registro aunque el archivo no exista
            }
        } catch (\Exception $e) {
            \Log::error("Image::deleteFromStorage - EXCEPCIÓN al eliminar de MinIO: " . $e->getMessage());
            \Log::error("Image::deleteFromStorage - Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

}
