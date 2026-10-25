<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryListRequest;
use App\Http\Requests\UpdateCategoryListRequest;
use App\Models\CategoryList;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class CategoryListController extends Controller
{
    protected $resourceRoute = 'categorylist';

    #[OA\Get(
            path: "/api/v2/categorylist",
            summary: "Display a listing of the CategoryList.",
            security: [
                [
                    'bearerAuth' => []
                ]
            ],
            tags: ["Product"],
            responses: [
                new OA\Response(response: Response::HTTP_OK, description: "CategoryList retrieved success"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
                new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
            ]
        )]
    public function index()
    {
       return parent::index();
    }

    /**
     * Store a newly created resource in storage.
     */
     #[OA\Post(
             path: "/api/v2/categorylist",
             summary: "Store a newly created resource in storage.",
             security: [
                 [
                     'bearerAuth' => []
                 ]
             ],
             tags: ["Product"],
             responses: [
                 new OA\Response(response: Response::HTTP_OK, description: "CategoryList created successfully"),
                 new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                 new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
                 new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
             ]
         )]
    public function store(StoreCategoryListRequest $request)
    {
        return $this->createFromAttributes($request->toArray());
    }

    /**
     * Display the specified resource.
     */
     #[OA\Get(
             path: "/api/v2/categorylist/{id}",
             summary: "Display the specified CategoryList.",
             security: [
                 [
                     'bearerAuth' => []
                 ]
             ],
             tags: ["Product"],
             parameters: [
                 new OA\Parameter(parameter: 'id', name: 'id',description: "CategoryList id",in: 'path', required: true, example: '1')
             ],
             responses: [
                 new OA\Response(response: Response::HTTP_OK, description: "CategoryList retrieved success"),
                 new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                 new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
                 new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
             ]
         )]
    public function show(CategoryList $categoryList)
    {
        return $this->getMainModelInstance($categoryList->id);
    }

    /**
     * Update the specified resource in storage.
     */
     #[OA\Put(
             path: "/api/v2/categorylist/{id}",
             summary: "Updates the specified CategoryList.",
             security: [
                 [
                     'bearerAuth' => []
                 ]
             ],
             tags: ["Product"],
             responses: [
                 new OA\Response(response: Response::HTTP_OK, description: "CategoryList updated successfully"),
                 new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                 new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
                 new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
             ]
         )]
    public function update(UpdateCategoryListRequest $request, CategoryList $categoryList)
    {
        return $this->updateModel($this->getMainModelInstance($categoryList->id),$request->toArray());
    }

    /**
     * Remove the specified resource from storage.
     */
     #[OA\Delete(
             path: "/api/v2/categorylist/{id}",
             summary: "Remove the specified CategoryList.",
             security: [
                 [
                     'bearerAuth' => []
                 ]
             ],
             tags: ["Product"],
             responses: [
                 new OA\Response(response: Response::HTTP_OK, description: "CategoryList deleted successfully"),
                 new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                 new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
                 new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
             ]
         )]
    public function destroy(CategoryList $categoryList)
    {
        return $this->destroyResourceEmptyResponse($categoryList);
    }
}
