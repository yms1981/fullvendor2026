<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper para verificar y crear la estructura de directorios uploads
 * 
 * Verifica:
 * 1. Que existe el directorio uploads
 * 2. Que existe uploads/products
 * 3. Que dentro de uploads/products existen carpetas con números de compañías
 * 4. Que dentro de cada carpeta de compañía en products existe categories
 * 5. Que dentro de categories existen carpetas con números de compañías
 */

/**
 * Verifica y crea la estructura de directorios uploads
 * 
 * @param object $companyModel Instancia del CompanyModel para obtener las compañías
 * @return void
 */
function verify_uploads_structure($companyModel = null)
{
   /* $basePath = FCPATH;
    $uploadsPath = $basePath . 'uploads';
    $productsPath = $uploadsPath . '/products';
    
    // 1. Verificar y crear uploads si no existe
    if (!is_dir($uploadsPath)) {
        @mkdir($uploadsPath, 0755, true);
    }
    
    // 2. Verificar y crear uploads/products si no existe
    if (!is_dir($productsPath)) {
        @mkdir($productsPath, 0755, true);
    }
    
    // 3. Obtener todas las compañías de la base de datos
    if ($companyModel !== null && method_exists($companyModel, 'all')) {
        $companies = $companyModel->all();
        
        if ($companies && (is_array($companies) || is_object($companies))) {
            foreach ($companies as $company) {
                $companyId = is_object($company) ? $company->company_id : $company['company_id'];
                
                if (!is_numeric($companyId)) {
                    continue;
                }
                
                // 4. Crear carpeta de compañía en products si no existe
                $companyProductsPath = $productsPath . '/' . $companyId;
                if (!is_dir($companyProductsPath)) {
                    @mkdir($companyProductsPath, 0755, true);
                }
                
                // 5. Crear carpeta categories dentro de la carpeta de compañía en products
                $categoriesPath = $companyProductsPath . '/categories';
                if (!is_dir($categoriesPath)) {
                    @mkdir($categoriesPath, 0755, true);
                }
                
                // 6. Crear carpeta de compañía dentro de categories
                $companyCategoriesPath = $categoriesPath . '/' . $companyId;
                if (!is_dir($companyCategoriesPath)) {
                    @mkdir($companyCategoriesPath, 0755, true);
                }
            }
        }
    } else {
        // Si no hay acceso al modelo, al menos verificar las carpetas existentes
        if (is_dir($productsPath)) {
            $items = @scandir($productsPath);
            if ($items !== false) {
                foreach ($items as $item) {
                    if ($item != '.' && $item != '..' && is_numeric($item)) {
                        $companyProductsPath = $productsPath . '/' . $item;
                        if (is_dir($companyProductsPath)) {
                            // Crear categories si no existe
                            $categoriesPath = $companyProductsPath . '/categories';
                            if (!is_dir($categoriesPath)) {
                                @mkdir($categoriesPath, 0755, true);
                            }
                            
                            // Crear carpeta de compañía dentro de categories
                            $companyCategoriesPath = $categoriesPath . '/' . $item;
                            if (!is_dir($companyCategoriesPath)) {
                                @mkdir($companyCategoriesPath, 0755, true);
                            }
                        }
                    }
                }
            }
        }
    }*/
}

