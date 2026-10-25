<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class PlaceController extends Controller
{
    /**
     * Obtener todos los lugares (países con estados y ciudades)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: "api/v2/places",
        summary: "Obtener todos los lugares (países con estados y ciudades)",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        tags: ["Places"],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Lista de todos los lugares con estructura jerárquica",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'sortname', type: 'string'),
                                    new OA\Property(property: 'phonecode', type: 'integer'),
                                    new OA\Property(property: 'time_zone', type: 'string'),
                                    new OA\Property(
                                        property: 'states',
                                        type: 'array',
                                        items: new OA\Items(
                                            type: 'object',
                                            properties: [
                                                new OA\Property(property: 'id', type: 'integer'),
                                                new OA\Property(property: 'name', type: 'string'),
                                                new OA\Property(property: 'country_id', type: 'integer'),
                                                new OA\Property(
                                                    property: 'cities',
                                                    type: 'array',
                                                    items: new OA\Items(
                                                        type: 'object',
                                                        properties: [
                                                            new OA\Property(property: 'id', type: 'integer'),
                                                            new OA\Property(property: 'name', type: 'string'),
                                                            new OA\Property(property: 'state_id', type: 'integer')
                                                        ]
                                                    )
                                                )
                                            ]
                                        )
                                    )
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "No autenticado"
            )
        ]
    )]
    public function index(Request $request)
    {
        // Obtener el usuario autenticado
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        // Cargar países con sus estados y ciudades usando eager loading
        $countries = Country::with([
            'states' => function ($query) {
                $query->orderBy('name');
            },
            'states.cities' => function ($query) {
                $query->orderBy('name');
            }
        ])
        ->orderBy('name')
        ->get();
        
        // Transformar la estructura para devolver la jerarquía anidada
        $places = $countries->map(function ($country) {
            return [
                'id' => $country->id,
                'name' => $country->name,
                'sortname' => $country->sortname,
                'phonecode' => $country->phonecode,
                'time_zone' => $country->time_zone,
                'phonemask' => $country->phonemask ?? null,
                'states' => $country->states->map(function ($state) {
                    return [
                        'id' => $state->id,
                        'name' => $state->name,
                        'country_id' => $state->country_id,
                        'cities' => $state->cities->map(function ($city) {
                            return [
                                'id' => $city->id,
                                'name' => $city->name,
                                'state_id' => $city->state_id,
                            ];
                        })->values()->all()
                    ];
                })->values()->all()
            ];
        });
        
        return response()->json([
            'data' => $places->values()->all()
        ]);
    }
}

