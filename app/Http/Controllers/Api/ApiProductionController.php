<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Production\ProductionCollection;
use App\Http\Resources\Production\ProductionResource;
use App\Models\Production;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class ApiProductionController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.verify');
    }

    #[OA\Get(
        path: '/api/productions',
        summary: 'Listar producciones',
        description: 'Obtiene el listado paginado de órdenes de producción.',
        tags: ['Productions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                description: 'Buscar por número de producción o compra.',
                schema: new OA\Schema(type: 'string', example: 'PROD-001')
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Cantidad de registros por página.',
                schema: new OA\Schema(type: 'integer', default: 10, example: 10)
            ),
            new OA\Parameter(
                name: 'date',
                in: 'query',
                required: false,
                description: 'Filtrar por fecha de creación (YYYY-MM-DD).',
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-09-15')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Producciones obtenidas correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 500, description: 'Error interno del servidor')
        ]
    )]
    public function index(Request $request)
    {
        $search   = $request->string('search');
        $per_page = $request->integer('per_page', 10);
        $date     = $request->string('date');

        // Eager load purchaseOrder para obtener el unit_price, colors y guides
        $query = Production::with(['purchaseOrder', 'colors', 'guides']);
        
        if ($search->isNotEmpty()) {
            $query->where(function ($q) use ($search) {
                $q->where('production_order_number', 'like', '%' . $search . '%')
                  ->orWhere('purchase_order_number', 'like', '%' . $search . '%');
            });
        }

        if ($date->isNotEmpty()) {
            $query->whereDate('created_at', $date);
        }

        $productions = $query->orderBy('id', 'desc')->paginate($per_page);

        return response()->json([
            'productions' => ProductionCollection::make($productions),
            'pagination'  => [
                'total'        => $productions->total(),
                'current_page' => $productions->currentPage(),
                'last_page'    => $productions->lastPage(),
                'per_page'     => $productions->perPage(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/productions',
        summary: 'Registrar producción',
        description: 'Crea un nuevo registro de producción.',
        tags: ['Productions'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['user_id', 'quantity', 'purchase_order_number', 'production_order_number', 'purchase_order_id', 'is_active'],
                    properties: [
                        new OA\Property(property: 'user_id', type: 'integer', example: 1),
                        new OA\Property(property: 'quantity', type: 'integer', example: 100),
                        new OA\Property(property: 'purchase_order_number', type: 'string', example: 'PO-001'),
                        new OA\Property(property: 'production_order_number', type: 'string', example: 'PROD-001'),
                        new OA\Property(property: 'purchase_order_id', type: 'integer', example: 1),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true)
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Producción creada correctamente'),
            new OA\Response(response: 422, description: 'Errores de validación'),
            new OA\Response(response: 401, description: 'No autorizado')
        ]
    )]
    public function store(Request $request)
    {
        try {
            $request->validate([
                'user_id'                 => ['required', 'integer', 'exists:users,id'],
                'quantity'                => ['required', 'integer', 'min:1'],
                'purchase_order_number'   => ['required', 'string', 'max:50'],
                'production_order_number' => ['required', 'string', 'max:50'],
                'purchase_order_id'       => ['required', 'integer', 'exists:purchase_orders,id'],
                'unit_price'              => ['nullable', 'numeric', 'min:0'],
                'is_active'               => ['required', 'boolean'],
            ], [
                'user_id.required'                 => 'El usuario es obligatorio.',
                'user_id.exists'                   => 'El usuario seleccionado no existe.',
                'quantity.required'                => 'La cantidad es obligatoria.',
                'purchase_order_number.required'   => 'El número de orden de compra es obligatorio.',
                'production_order_number.required' => 'El número de orden de producción es obligatorio.',
                'purchase_order_id.required'       => 'El ID de la orden de compra es obligatorio.',
                'purchase_order_id.exists'         => 'La orden de compra seleccionada no existe.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'mensaje' => 'Errores de validación',
                'errors'  => $e->errors()
            ], 422);
        }

        $production = new Production();
        $production->user_id                 = $request->user_id;
        $production->quantity                = $request->quantity;
        $production->purchase_order_number   = $request->purchase_order_number;
        $production->production_order_number = $request->production_order_number;
        $production->purchase_order_id       = $request->purchase_order_id;
        $production->unit_price              = $request->unit_price;
        $production->is_active               = $request->is_active;
        if ($request->has('production_date') && $request->production_date) {
            $production->created_at = $request->production_date;
        }
        $production->save();

        // Sincronizar colores y guías en las tablas pivote
        if ($request->has('colors')) {
            $production->colors()->sync($request->input('colors', []));
        }
        if ($request->has('guides')) {
            $guideIds = array_slice((array) $request->input('guides', []), 0, 10); // máx 10
            $production->guides()->sync($guideIds);
        }

        return response()->json([
            'codigo'  => 200,
            'mensaje' => 'Producción creada correctamente',
            'production' => ProductionResource::make($production->load('colors', 'guides')),
        ], 200);
    }

    #[OA\Put(
        path: '/api/productions/{id}',
        summary: 'Actualizar producción',
        description: 'Actualiza la información de un registro de producción.',
        tags: ['Productions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID de la producción',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['user_id', 'quantity', 'purchase_order_number', 'production_order_number', 'purchase_order_id', 'is_active'],
                    properties: [
                        new OA\Property(property: 'user_id', type: 'integer', example: 1),
                        new OA\Property(property: 'quantity', type: 'integer', example: 150),
                        new OA\Property(property: 'purchase_order_number', type: 'string', example: 'PO-001'),
                        new OA\Property(property: 'production_order_number', type: 'string', example: 'PROD-001'),
                        new OA\Property(property: 'purchase_order_id', type: 'integer', example: 1),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true)
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Producción actualizada correctamente'),
            new OA\Response(response: 404, description: 'Producción no encontrada'),
            new OA\Response(response: 422, description: 'Errores de validación'),
            new OA\Response(response: 401, description: 'No autorizado')
        ]
    )]
    public function update(Request $request, Production $production)
    {
        try {
            $request->validate([
                'user_id'                 => ['required', 'integer', 'exists:users,id'],
                'quantity'                => ['required', 'integer', 'min:1'],
                'purchase_order_number'   => ['required', 'string', 'max:50'],
                'production_order_number' => ['required', 'string', 'max:50'],
                'purchase_order_id'       => ['required', 'integer', 'exists:purchase_orders,id'],
                'unit_price'              => ['nullable', 'numeric', 'min:0'],
                'is_active'               => ['required', 'boolean'],
            ], [
                'user_id.required'                 => 'El usuario es obligatorio.',
                'user_id.exists'                   => 'El usuario seleccionado no existe.',
                'quantity.required'                => 'La cantidad es obligatoria.',
                'purchase_order_number.required'   => 'El número de orden de compra es obligatorio.',
                'production_order_number.required' => 'El número de orden de producción es obligatorio.',
                'purchase_order_id.required'       => 'El ID de la orden de compra es obligatorio.',
                'purchase_order_id.exists'         => 'La orden de compra seleccionada no existe.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'mensaje' => 'Errores de validación',
                'errors'  => $e->errors()
            ], 422);
        }

        $production->user_id                 = $request->user_id;
        $production->quantity                = $request->quantity;
        $production->purchase_order_number   = $request->purchase_order_number;
        $production->production_order_number = $request->production_order_number;
        $production->purchase_order_id       = $request->purchase_order_id;
        $production->unit_price              = $request->unit_price;
        $production->is_active               = $request->is_active;
        if ($request->has('production_date') && $request->production_date) {
            $production->created_at = $request->production_date;
        }
        $production->save();

        // Sincronizar colores y guías en las tablas pivote
        if ($request->has('colors')) {
            $production->colors()->sync($request->input('colors', []));
        }
        if ($request->has('guides')) {
            $guideIds = array_slice((array) $request->input('guides', []), 0, 10); // máx 10
            $production->guides()->sync($guideIds);
        }

        return response()->json([
            'mensaje' => 'Producción actualizada correctamente',
            'production' => ProductionResource::make($production->load('purchaseOrder', 'colors', 'guides')),
        ], 200);
    }
}
