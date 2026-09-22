<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Color\ColorCollection;
use App\Http\Resources\Color\ColorResource;
use App\Models\Color;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class ApiColorController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.verify');
        // Cuando agregues el permiso 'listar_color' en tu seeder, puedes descomentar:
        // $this->middleware('can:listar_color')->only('index');
    }

    #[OA\Get(
        path: '/api/colores',
        summary: 'Listar colores',
        description: 'Obtiene el listado paginado de colores.',
        tags: ['Colores'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                description: 'Buscar por nombre.',
                schema: new OA\Schema(type: 'string', example: 'Rojo')
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Cantidad de registros por página.',
                schema: new OA\Schema(type: 'integer', default: 10, example: 10)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Colores obtenidos correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 500, description: 'Error interno del servidor')
        ]
    )]
    public function index(Request $request)
    {
        $search   = $request->string('search');
        $per_page = $request->integer('per_page', 10);

        $colores = Color::where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            })
            ->orderBy('id', 'desc')
            ->paginate($per_page);

        return response()->json([
            'colores'    => ColorCollection::make($colores),
            'pagination' => [
                'total'        => $colores->total(),
                'current_page' => $colores->currentPage(),
                'last_page'    => $colores->lastPage(),
                'per_page'     => $colores->perPage(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/colores',
        summary: 'Registrar color',
        description: 'Crea un nuevo color.',
        tags: ['Colores'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['name', 'abbreviation', 'is_active'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'Azul'),
                        new OA\Property(property: 'abbreviation', type: 'string', example: 'AZ'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true, description: 'true activo, false inactivo')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Color creado correctamente'),
            new OA\Response(response: 422, description: 'Errores de validación'),
            new OA\Response(response: 401, description: 'No autorizado')
        ]
    )]
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:50', 'unique:colors,name'],
                'abbreviation' => ['nullable', 'string', 'max:10', 'unique:colors,abbreviation'],
                'is_active' => ['required', 'boolean'],
            ], [
                'name.required' => 'El nombre es obligatorio.',
                'name.unique'   => 'El nombre ya está registrado.',
                'name.max'      => 'El nombre no puede exceder 50 caracteres.',
                'abbreviation.unique'   => 'La abreviatura ya está registrada.',
                'abbreviation.max'      => 'La abreviatura no puede exceder 10 caracteres.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'mensaje' => 'Errores de validación',
                'errors'  => $e->errors()
            ], 422);
        }

        $color = new Color();
        $color->name = $request->name;
        $color->abbreviation = $request->abbreviation ?: strtoupper(substr($request->name, 0, 3));
        $color->is_active = $request->is_active;
        $color->save();

        return response()->json([
            'codigo'  => 200,
            'mensaje' => 'Color creado correctamente',
            'color'   => ColorResource::make($color),
        ], 200);
    }

    #[OA\Put(
        path: '/api/colores/{id}',
        summary: 'Actualizar color',
        description: 'Actualiza la información de un color.',
        tags: ['Colores'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID del color',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['name', 'abbreviation', 'is_active'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'Rojo Oscuro'),
                        new OA\Property(property: 'abbreviation', type: 'string', example: 'ROD'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true, description: 'true activo, false inactivo')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Color actualizado correctamente'),
            new OA\Response(response: 404, description: 'Color no encontrado'),
            new OA\Response(response: 422, description: 'Errores de validación'),
            new OA\Response(response: 401, description: 'No autorizado')
        ]
    )]
    public function update(Request $request, Color $color)
    {
        try {
            $request->validate([
                'name'         => ['required', 'string', 'max:50', 'unique:colors,name,' . $color->id],
                'abbreviation' => ['nullable', 'string', 'max:10', 'unique:colors,abbreviation,' . $color->id],
                'is_active'    => ['required', 'boolean'],
            ], [
                'name.required' => 'El nombre es obligatorio.',
                'name.unique'   => 'El nombre ya está registrado.',
                'name.max'      => 'El nombre no puede exceder 50 caracteres.',
                'abbreviation.unique'   => 'La abreviatura ya está registrada.',
                'abbreviation.max'      => 'La abreviatura no puede exceder 10 caracteres.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'mensaje' => 'Errores de validación',
                'errors'  => $e->errors()
            ], 422);
        }

        $color->name      = $request->name;
        $color->abbreviation = $request->abbreviation ?: strtoupper(substr($request->name, 0, 3));
        $color->is_active = $request->is_active;
        $color->save();

        return response()->json([
            'mensaje' => 'Color actualizado correctamente',
            'color'   => ColorResource::make($color),
        ], 200);
    }
}
