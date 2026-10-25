<?php
/**
 * Script de verificación de estructura de directorios uploads
 * 
 * Verifica:
 * 1. Que existe el directorio uploads
 * 2. Que existe uploads/products
 * 3. Que dentro de uploads/products existen carpetas con números de compañías
 * 4. Que existe uploads/categories
 * 5. Que dentro de uploads/categories existen carpetas con números de compañías
 */

// Configuración
$basePath = __DIR__;
$uploadsPath = $basePath . '/uploads';
$productsPath = $uploadsPath . '/products';
$categoriesPath = $uploadsPath . '/categories';

// Resultados de verificación
$results = [
    'uploads_exists' => false,
    'products_exists' => false,
    'products_company_folders' => [],
    'categories_exists' => false,
    'categories_company_folders' => [],
    'errors' => [],
    'warnings' => []
];

echo "========================================\n";
echo "VERIFICACIÓN DE ESTRUCTURA DE DIRECTORIOS\n";
echo "========================================\n\n";

// 1. Verificar que existe uploads
echo "1. Verificando directorio 'uploads'...\n";
if (is_dir($uploadsPath)) {
    $results['uploads_exists'] = true;
    echo "   ✓ El directorio 'uploads' existe\n";
} else {
    $results['errors'][] = "El directorio 'uploads' NO existe en: $uploadsPath";
    echo "   ✗ El directorio 'uploads' NO existe\n";
    echo "   Ruta esperada: $uploadsPath\n";
}

echo "\n";

// 2. Verificar que existe uploads/products
echo "2. Verificando directorio 'uploads/products'...\n";
if (is_dir($productsPath)) {
    $results['products_exists'] = true;
    echo "   ✓ El directorio 'uploads/products' existe\n";
} else {
    $results['errors'][] = "El directorio 'uploads/products' NO existe en: $productsPath";
    echo "   ✗ El directorio 'uploads/products' NO existe\n";
    echo "   Ruta esperada: $productsPath\n";
}

echo "\n";

// 3. Verificar carpetas de compañías en uploads/products
echo "3. Verificando carpetas de compañías en 'uploads/products'...\n";
if ($results['products_exists']) {
    $items = @scandir($productsPath);
    if ($items === false) {
        $results['errors'][] = "No se puede leer el directorio 'uploads/products'";
        echo "   ✗ Error al leer el directorio\n";
    } else {
        $companyFolders = array_filter($items, function($item) use ($productsPath) {
            $fullPath = $productsPath . '/' . $item;
            return is_dir($fullPath) && 
                   $item != '.' && 
                   $item != '..' && 
                   is_numeric($item);
        });
        
        $results['products_company_folders'] = array_values($companyFolders);
        $count = count($results['products_company_folders']);
        
        if ($count > 0) {
            echo "   ✓ Se encontraron $count carpeta(s) de compañías:\n";
            foreach ($results['products_company_folders'] as $folder) {
                echo "      - $folder\n";
            }
        } else {
            $results['warnings'][] = "No se encontraron carpetas de compañías en 'uploads/products'";
            echo "   ⚠ No se encontraron carpetas de compañías (números) en 'uploads/products'\n";
        }
    }
} else {
    echo "   ⚠ No se puede verificar (el directorio 'uploads/products' no existe)\n";
}

echo "\n";

// 4. Verificar que existe uploads/categories
echo "4. Verificando directorio 'uploads/categories'...\n";
if (is_dir($categoriesPath)) {
    $results['categories_exists'] = true;
    echo "   ✓ El directorio 'uploads/categories' existe\n";
} else {
    $results['errors'][] = "El directorio 'uploads/categories' NO existe en: $categoriesPath";
    echo "   ✗ El directorio 'uploads/categories' NO existe\n";
    echo "   Ruta esperada: $categoriesPath\n";
}

echo "\n";

// 5. Verificar carpetas de compañías en uploads/categories
echo "5. Verificando carpetas de compañías en 'uploads/categories'...\n";
if ($results['categories_exists']) {
    $items = @scandir($categoriesPath);
    if ($items === false) {
        $results['errors'][] = "No se puede leer el directorio 'uploads/categories'";
        echo "   ✗ Error al leer el directorio\n";
    } else {
        $companyFolders = array_filter($items, function($item) use ($categoriesPath) {
            $fullPath = $categoriesPath . '/' . $item;
            return is_dir($fullPath) && 
                   $item != '.' && 
                   $item != '..' && 
                   is_numeric($item);
        });
        
        $results['categories_company_folders'] = array_values($companyFolders);
        $count = count($results['categories_company_folders']);
        
        if ($count > 0) {
            echo "   ✓ Se encontraron $count carpeta(s) de compañías:\n";
            foreach ($results['categories_company_folders'] as $folder) {
                echo "      - $folder\n";
            }
        } else {
            $results['warnings'][] = "No se encontraron carpetas de compañías en 'uploads/categories'";
            echo "   ⚠ No se encontraron carpetas de compañías (números) en 'uploads/categories'\n";
        }
    }
} else {
    echo "   ⚠ No se puede verificar (el directorio 'uploads/categories' no existe)\n";
}

echo "\n";

// Resumen final
echo "========================================\n";
echo "RESUMEN DE VERIFICACIÓN\n";
echo "========================================\n\n";

$allOk = true;

if (!$results['uploads_exists']) {
    echo "✗ El directorio 'uploads' NO existe\n";
    $allOk = false;
} else {
    echo "✓ El directorio 'uploads' existe\n";
}

if (!$results['products_exists']) {
    echo "✗ El directorio 'uploads/products' NO existe\n";
    $allOk = false;
} else {
    echo "✓ El directorio 'uploads/products' existe\n";
}

if (count($results['products_company_folders']) == 0 && $results['products_exists']) {
    echo "⚠ No se encontraron carpetas de compañías en 'uploads/products'\n";
} else if (count($results['products_company_folders']) > 0) {
    echo "✓ Se encontraron " . count($results['products_company_folders']) . " carpeta(s) de compañías en 'uploads/products'\n";
}

if (!$results['categories_exists']) {
    echo "✗ El directorio 'uploads/categories' NO existe\n";
    $allOk = false;
} else {
    echo "✓ El directorio 'uploads/categories' existe\n";
}

if (count($results['categories_company_folders']) == 0 && $results['categories_exists']) {
    echo "⚠ No se encontraron carpetas de compañías en 'uploads/categories'\n";
} else if (count($results['categories_company_folders']) > 0) {
    echo "✓ Se encontraron " . count($results['categories_company_folders']) . " carpeta(s) de compañías en 'uploads/categories'\n";
}

echo "\n";

if (count($results['errors']) > 0) {
    echo "ERRORES ENCONTRADOS:\n";
    foreach ($results['errors'] as $error) {
        echo "  - $error\n";
    }
    echo "\n";
    $allOk = false;
}

if (count($results['warnings']) > 0) {
    echo "ADVERTENCIAS:\n";
    foreach ($results['warnings'] as $warning) {
        echo "  - $warning\n";
    }
    echo "\n";
}

if ($allOk && count($results['warnings']) == 0) {
    echo "✓ TODAS LAS VERIFICACIONES PASARON CORRECTAMENTE\n";
    exit(0);
} else {
    echo "⚠ SE ENCONTRARON PROBLEMAS O ADVERTENCIAS\n";
    exit(1);
}



