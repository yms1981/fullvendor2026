<?php

namespace App\Http\Controllers\Mobile;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends MobileController
{
    /**
     * Listar categorías
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        
        if (!$companyId) {
            return $this->error('company_id es requerido', null, 422);
        }
        
        $baseUrl = $request->getSchemeAndHttpHost();
        $categories = Category::where('company_id', $companyId)
            ->where('category_status', 1)
            ->select('cat_id', 'category_name', 'images')
            ->orderBy('category_name')
            ->get()
            ->map(function ($category) use ($baseUrl) {
                return [
                    'id' => $category->cat_id,
                    'name' => $category->category_name,
                    'image' => $category->images ? $baseUrl . "/images/{$category->images}" : null,
                ];
            });
        
        return $this->success($categories);
    }
    
    /**
     * Obtener una categoría específica
     * 
     * @param Request $request
     * @param int $categoryId
     * @return JsonResponse
     */
    public function show(Request $request, int $categoryId): JsonResponse
    {
        $category = Category::find($categoryId);
        
        if (!$category) {
            return $this->error('Categoría no encontrada', null, 404);
        }
        
        $baseUrl = $request->getSchemeAndHttpHost();
        return $this->success([
            'id' => $category->cat_id,
            'name' => $category->category_name,
            'image' => $category->images ? $baseUrl . "/images/{$category->images}" : null,
        ]);
    }
    
    /**
     * Obtener productos de una categoría
     * 
     * @param Request $request
     * @param int $categoryId
     * @return JsonResponse
     */
    public function products(Request $request, int $categoryId): JsonResponse
    {
        // Implementar lógica para obtener productos de la categoría
        return $this->success([]);
    }
}

