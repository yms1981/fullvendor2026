<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImageRequest;
use App\Http\Requests\UpdateImageRequest;
use App\Models\CatalogCart;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;

class ImageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return parent::index();
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreImageRequest $request)
    {
        $request = request();

        if (!$request->hasFile('contents') && !$request->has('contents')) {
            // Algunos clientes multipart envían el binario sin filename; intentar primer archivo.
            $allFiles = $request->allFiles();
            if (! empty($allFiles['contents'])) {
                // ok
            } elseif (! empty($allFiles)) {
                $first = reset($allFiles);
                if ($first instanceof \Illuminate\Http\UploadedFile) {
                    $request->files->set('contents', $first);
                }
            }
        }

        if (!$request->hasFile('contents') && !$request->has('contents')) {
            return response()->json(['error' => 'No image file provided'], 400);
        }

        $companyId = $request->input('company_id');
        $imageName = (string) $request->input('name');
        $imageFile = $request->file('contents') ?? $request->input('contents');

        $originalName = $request->hasFile('contents')
            ? (string) $request->file('contents')->getClientOriginalName()
            : ($imageName !== '' ? $imageName : 'upload');

        // Nombre base sin extensión: putContent normaliza a JPEG (u otro formato configurado).
        $baseName = pathinfo($imageName !== '' ? $imageName : $originalName, PATHINFO_FILENAME);
        if ($baseName === '' || $baseName === '.' || $baseName === false) {
            $baseName = (string) \Illuminate\Support\Str::uuid();
        }

        $origExt = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($origExt === 'jpeg') {
            $origExt = 'jpg';
        }
        if ($origExt === '' || ! in_array($origExt, Image::ACCEPTED_EXTENSIONS, true)) {
            $origExt = Image::STORAGE_EXTENSION;
        }

        $image = new Image();
        $image->company_id = $companyId;
        $image->name = $baseName . '.' . $origExt;
        $image->disk = Image::defaultDisk();
        $image->extension = $origExt;
        $image->mime_type = $request->hasFile('contents')
            ? (string) ($request->file('contents')->getMimeType() ?: Image::STORAGE_MIME)
            : Image::STORAGE_MIME;
        $image->type = $image->mime_type;
        $image->original_name = $originalName;

        if ($request->hasFile('contents')) {
            $content = $request->file('contents')->getContent();
        } else {
            $content = $imageFile;
        }

        Log::debug($image);
        $imageType = strtolower(trim((string) $request->input('type', 'product')));
        if (! in_array($imageType, ['product', 'category', 'company', 'user', 'chat', 'catalog'], true)) {
            $imageType = 'product';
        }
        $image = $image->putContent($content, $companyId, $imageType);

        return [
            'uuid' => $image->uuid,
            'name' => $image->name,
            'extension' => $image->extension ?: Image::STORAGE_EXTENSION,
            'mime_type' => $image->mime_type ?: Image::STORAGE_MIME,
            'size' => $image->size,
        ];
    }

    /**
     * Display the specified resource.
     */
    public function show(Image $image)
    {
        return $image;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateImageRequest $request, Image $image)
    {
        return $this->updateModel($image,$request->toArray());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Image $image)
    {
        return $this->destroyResourceEmptyResponse($image);
    }

    /**
     * Placeholder público (categorías/productos sin imagen y onerror del listado).
     * Debe leerse del disco local: el default FILESYSTEM_DISK=s3/minio no tiene este PNG.
     */
    public function noImage(string $imageType = 'png')
    {
        return $this->noImageResponse();
    }

    /**
     * @param Image $image
     * @param string $imageType
     * @return \Illuminate\Http\Response
     */
    public function image(Image $image, string $imageType)
    {
        $imageType = $this->normalizeImageExtension($imageType);

        // Preferir binario crudo: evita fallos de Imagick al convertir (p.ej. forzar webp).
        $raw = $this->streamRawImageFromModel($image, $imageType);
        if ($raw->getStatusCode() === 200) {
            return $raw;
        }

        try {
            $imageContent = $image->image($imageType);

            return response($imageContent->encodeByExtension($imageType))
                ->header('Content-Type', $this->imageContentType($imageType));
        } catch (\Throwable $e) {
            Log::warning('ImageController::image intervention failed: ' . $e->getMessage(), [
                'uuid' => $image->uuid ?? null,
            ]);

            return $raw->getStatusCode() === 404 ? $raw : $this->streamRawImageFromModel($image, $imageType);
        }
    }

    /**
     * Sirve la imagen por UUID usando la extensión real del registro (sin forzar .webp).
     */
    public function imageByUuid(Image $image)
    {
        return $this->image($image, $this->extensionFromImageModel($image));
    }

    /**
     * Miniatura escalada usando el formato real del registro (jpg/png/gif/webp/…).
     * Ruta: GET images/{uuid}_{width}
     */
    public function imageScaledByWidth(Image $image, int $width)
    {
        return $this->imageScaledByWith($image, $width, $this->extensionFromImageModel($image));
    }

    /**
     * @param Image $image
     * @param string $imageType
     * @return \Illuminate\Http\Response
     */
    public function imageScaledByWith(Image $image, int $with, string $imageType)
    {
        $requestedType = $this->normalizeImageExtension($imageType);
        $modelExt = $this->extensionFromImageModel($image);
        // Preferir siempre el formato del archivo en almacenamiento; la extensión de la URL es opcional.
        $imageType = $modelExt !== '' ? $modelExt : $requestedType;
        $width = max(1, min(2000, (int) $with));

        // Preferir raw + scale: si Intervention falla, devolver el binario completo
        // (la grilla debe ver la foto aunque no haya miniatura).
        try {
            $imageContent = $image->image($imageType, $width);

            return response($imageContent->encodeByExtension($imageType))
                ->header('Content-Type', $this->imageContentType($imageType));
        } catch (\Throwable $e) {
            Log::warning('ImageController::imageScaledByWith intervention failed, streaming raw: ' . $e->getMessage(), [
                'uuid' => $image->uuid ?? null,
                'requested' => $requestedType,
                'model_ext' => $modelExt,
                'width' => $width,
            ]);

            if ($requestedType !== $imageType) {
                try {
                    $imageContent = $image->image($requestedType, $width);

                    return response($imageContent->encodeByExtension($requestedType))
                        ->header('Content-Type', $this->imageContentType($requestedType));
                } catch (\Throwable $e2) {
                    Log::warning('ImageController::imageScaledByWith requested-ext retry failed: ' . $e2->getMessage(), [
                        'uuid' => $image->uuid ?? null,
                    ]);
                }
            }

            return $this->streamRawImageFromModel($image, $imageType);
        }
    }

    /**
     * Extensión real del registro de imagen (jpg/png/gif/webp/bmp/avif…).
     */
    private function extensionFromImageModel(Image $image): string
    {
        $candidates = [
            $image->extension ?? null,
            $image->name ? pathinfo((string) $image->name, PATHINFO_EXTENSION) : null,
            $image->original_name ? pathinfo((string) $image->original_name, PATHINFO_EXTENSION) : null,
            $image->path ? pathinfo((string) $image->path, PATHINFO_EXTENSION) : null,
            $image->mime_type ? preg_replace('#^image/#i', '', (string) $image->mime_type) : null,
        ];

        foreach ($candidates as $raw) {
            $ext = $this->parseKnownImageExtension($raw);
            if ($ext !== null) {
                return $ext;
            }
        }

        return Image::storageExtension();
    }

    /**
     * @param mixed $raw
     */
    private function parseKnownImageExtension($raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $t = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string) $raw) ?? '');
        if ($t === '') {
            return null;
        }
        if ($t === 'jpeg') {
            $t = 'jpg';
        }
        $valid = ['jpg', 'png', 'gif', 'webp', 'bmp', 'avif'];

        return in_array($t, $valid, true) ? $t : null;
    }

    private function normalizeImageExtension(string $imageType): string
    {
        return $this->parseKnownImageExtension($imageType) ?? 'png';
    }

    private function imageContentType(string $imageType): string
    {
        $t = $this->normalizeImageExtension($imageType);

        return 'image/' . ($t === 'jpg' ? 'jpeg' : $t);
    }

    /**
     * Devuelve el binario tal cual en MinIO/disco, sin pasar por Intervention (evita 500 si Imagick falla).
     * En images_local intenta BinaryFileResponse (más rápido, menos memoria).
     */
    private function streamRawImageFromModel(Image $image, string $requestedExt): \Symfony\Component\HttpFoundation\Response
    {
        try {
            $mime = $image->mime_type;
            $ext = $this->extensionFromImageModel($image);
            if ($ext === '' || $ext === null) {
                $ext = $this->normalizeImageExtension($requestedExt);
            }
            if (! is_string($mime) || $mime === '' || ! str_starts_with($mime, 'image/')) {
                $mime = $this->imageContentType($ext);
            }

            $cacheHeaders = [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=86400, immutable',
            ];

            // Disco local: servir el archivo directamente (sin cargar todo en RAM).
            if (Image::usesDirectoryLayout($image->disk ?: Image::defaultDisk())) {
                $absolute = $this->resolveLocalAbsolutePath($image);
                if ($absolute !== null) {
                    return response()->file($absolute, $cacheHeaders);
                }
            }

            $bytes = $image->getRawContent();
            if ($bytes === null || $bytes === '') {
                Log::warning('ImageController::streamRawImageFromModel not found', [
                    'uuid' => $image->uuid ?? null,
                    'disk' => $image->disk ?? null,
                    'path' => $image->path ?? null,
                    'name' => $image->name ?? null,
                ]);

                return response('Not found', 404);
            }

            return response($bytes)->withHeaders($cacheHeaders);
        } catch (\Throwable $e) {
            Log::error('ImageController::streamRawImageFromModel: ' . $e->getMessage());

            return response('Error', 500);
        }
    }

    /**
     * Ruta absoluta en disco para images_local / legacy.
     */
    private function resolveLocalAbsolutePath(Image $image): ?string
    {
        $candidates = [];
        $relative = trim((string) ($image->path ?? ''));
        if ($relative !== '') {
            $candidates[] = $relative;
        }
        foreach ($image->candidateStoragePaths() as $p) {
            if (is_string($p) && $p !== '') {
                $candidates[] = $p;
            }
        }

        $roots = array_values(array_unique(array_filter([
            config('filesystems.disks.images_local.root'),
            env('IMAGES_LOCAL_ROOT'),
            env('MVC_UPLOADS_PATH'),
            is_dir('/var/www/mvc_uploads') ? '/var/www/mvc_uploads' : null,
            dirname(base_path()) . '/mvc/uploads',
        ])));

        foreach ($roots as $root) {
            foreach ($candidates as $rel) {
                $full = rtrim((string) $root, '/') . '/' . ltrim((string) $rel, '/');
                if (is_file($full) && is_readable($full)) {
                    return $full;
                }
            }
        }

        return null;
    }

    public function logo()
    {
        return $this->staticLocalImageResponse('images/logos/logo.png');
    }

    public function isologo()
    {
        return $this->staticLocalImageResponse('images/logos/isologo.png');
    }

    /**
     * Assets estáticos en storage/app (disco local), no en MinIO.
     */
    private function staticLocalImageResponse(string $path): \Illuminate\Http\Response
    {
        $content = null;
        foreach (['local', 'public'] as $disk) {
            try {
                $bytes = Storage::disk($disk)->get($path);
                if (is_string($bytes) && $bytes !== '') {
                    $content = $bytes;
                    break;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
        if ($content === null || $content === '') {
            return $this->noImageResponse();
        }

        return response($content)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'private, max-age=3600');
    }

    /**
     * Sirve una imagen desde MinIO para el catálogo público.
     * Ruta pública: GET images/catalog/{image}.{imageType}?token={cart_token}
     * Valida el token del carrito y devuelve la imagen desde MinIO. Ante cualquier error devuelve no-image (200).
     */
    public function imageForCatalog(string $image, string $imageType, Request $request)
    {
        try {
            $token = $request->query('token');
            if (empty($token)) {
                return $this->noImageResponse();
            }
            // Buscar directamente en tabla catalog_cart (no en orders)
            $cart = \DB::table('catalog_cart')
                ->whereNull('deleted_at')
                ->where('token_kor', $token)
                ->first();
            if (!$cart) {
                return $this->noImageResponse();
            }
            $imageModel = Image::where('uuid', $image)->first();
            if (!$imageModel) {
                return $this->noImageResponse();
            }
            $ext = strtolower(preg_replace('/[^a-z0-9]/', '', $imageType));
            $validExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $validExt, true)) {
                $ext = !empty($imageModel->extension) ? strtolower($imageModel->extension) : 'png';
                if (!in_array($ext, $validExt, true)) {
                    $ext = 'png';
                }
            }
            $imageContent = $imageModel->image($ext);
            if (!$imageContent) {
                return $this->noImageResponse();
            }
            $encoded = $imageContent->encodeByExtension($ext);
            return response((string) $encoded)
                ->header('Content-Type', 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext))
                ->header('Cache-Control', 'private, max-age=3600');
        } catch (\Throwable $e) {
            Log::warning('ImageController::imageForCatalog: ' . $e->getMessage(), [
                'uuid' => $image ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->noImageResponse();
        }
    }

    /**
     * PNG con fondo blanco convertido a transparente (catálogo / estudio).
     * Ruta: GET images/{uuid}_nobg.png  o  images/{uuid}_{width}_nobg.png
     */
    public function imageNoBackground(Image $image, ?int $width = null)
    {
        $width = $width !== null ? max(16, min(1200, (int) $width)) : null;
        $cacheKey = 'image-cache/nobg/'.$image->uuid.($width ? '_'.$width : '').'.png';

        try {
            if (Storage::disk('local')->exists($cacheKey)) {
                $cached = Storage::disk('local')->get($cacheKey);
                if (is_string($cached) && $cached !== '') {
                    return response($cached)
                        ->header('Content-Type', 'image/png')
                        ->header('Cache-Control', 'public, max-age=604800');
                }
            }
        } catch (\Throwable $e) {
            // regenerar
        }

        try {
            $png = $image->encodeWithoutWhiteBackground($width);
            try {
                Storage::disk('local')->put($cacheKey, $png);
            } catch (\Throwable $e) {
                Log::debug('imageNoBackground cache write failed: '.$e->getMessage());
            }

            return response($png)
                ->header('Content-Type', 'image/png')
                ->header('Cache-Control', 'public, max-age=604800');
        } catch (\Throwable $e) {
            Log::warning('ImageController::imageNoBackground failed: '.$e->getMessage(), [
                'uuid' => $image->uuid ?? null,
            ]);

            // Fallback: imagen original
            return $this->imageByUuid($image);
        }
    }

    /**
     * Respuesta con imagen por defecto (siempre 200 para que el <img> no falle).
     */
    private function noImageResponse(): \Illuminate\Http\Response
    {
        $content = null;
        foreach (['local', 'minio', null] as $disk) {
            try {
                $content = $disk ? Storage::disk($disk)->get('images/no-image.png') : Storage::get('images/no-image.png');
                if (!empty($content)) {
                    break;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
        if (empty($content)) {
            // 1x1 PNG transparente para no devolver cuerpo vacío
            $content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        }
        return response($content)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'private, max-age=60');
    }
}
