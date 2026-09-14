<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseOrder\PurchaseOrderCollection;
use App\Http\Resources\PurchaseOrder\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class ApiPurchaseOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.verify');
    }

    #[OA\Get(
        path: '/api/purchase-orders',
        summary: 'Listar órdenes de compra',
        description: 'Obtiene el listado paginado de órdenes de compra.',
        tags: ['Purchase Orders'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                description: 'Buscar por número de orden.',
                schema: new OA\Schema(type: 'string', example: 'PO-001')
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
            new OA\Response(response: 200, description: 'Órdenes obtenidas correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 500, description: 'Error interno del servidor')
        ]
    )]
    public function index(Request $request)
    {
        $search   = $request->string('search');
        $per_page = $request->integer('per_page', 10);

        $purchaseOrders = PurchaseOrder::where(function ($q) use ($search) {
                $q->where('order_number', 'like', '%' . $search . '%');
            })
            ->orderBy('id', 'desc')
            ->paginate($per_page);

        return response()->json([
            'purchase_orders' => PurchaseOrderCollection::make($purchaseOrders),
            'pagination'      => [
                'total'        => $purchaseOrders->total(),
                'current_page' => $purchaseOrders->currentPage(),
                'last_page'    => $purchaseOrders->lastPage(),
                'per_page'     => $purchaseOrders->perPage(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/purchase-orders',
        summary: 'Registrar orden de compra',
        description: 'Crea una nueva orden de compra.',
        tags: ['Purchase Orders'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['order_number', 'unit_price', 'is_active'],
                    properties: [
                        new OA\Property(property: 'order_number', type: 'string', example: 'PO-001'),
                        new OA\Property(property: 'unit_price', type: 'number', format: 'float', example: 12.50),
                        new OA\Property(property: 'issue_date', type: 'string', format: 'date', nullable: true, example: '2026-07-30'),
                        new OA\Property(property: 'attached_file', type: 'string', format: 'binary', nullable: true),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true)
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Orden creada correctamente'),
            new OA\Response(response: 422, description: 'Errores de validación'),
            new OA\Response(response: 401, description: 'No autorizado')
        ]
    )]
    public function store(Request $request)
    {
        try {
            $request->validate([
                'order_number'  => ['required', 'string', 'max:50', 'unique:purchase_orders,order_number'],
                'unit_price'    => ['required', 'numeric', 'min:0'],
                'issue_date'    => ['nullable', 'date'],
                'attached_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // max 5MB
                'is_active'     => ['required', 'boolean'],
            ], [
                'order_number.required' => 'El número de orden es obligatorio.',
                'order_number.unique'   => 'El número de orden ya está registrado.',
                'unit_price.required'   => 'El precio unitario es obligatorio.',
                'unit_price.numeric'    => 'El precio unitario debe ser un número.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'mensaje' => 'Errores de validación',
                'errors'  => $e->errors()
            ], 422);
        }

        $purchaseOrder = new PurchaseOrder();
        $purchaseOrder->order_number = $request->order_number;
        $purchaseOrder->unit_price   = $request->unit_price;
        $purchaseOrder->issue_date   = $request->issue_date;
        $purchaseOrder->is_active    = $request->is_active;

        if ($request->hasFile('attached_file')) {
            $path = $request->file('attached_file')->store('purchase_orders', 'public');
            $purchaseOrder->attached_file = $path;
        }

        $purchaseOrder->save();

        return response()->json([
            'codigo'  => 200,
            'mensaje' => 'Orden de compra creada correctamente',
            'purchase_order' => PurchaseOrderResource::make($purchaseOrder),
        ], 200);
    }

    #[OA\Post(
        path: '/api/purchase-orders/{id}',
        summary: 'Actualizar orden de compra',
        description: 'Actualiza la información de una orden de compra. Usa POST enviando _method=PUT debido al soporte de envío de archivos.',
        tags: ['Purchase Orders'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID de la orden de compra',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['order_number', 'unit_price', 'is_active', '_method'],
                    properties: [
                        new OA\Property(property: 'order_number', type: 'string', example: 'PO-001'),
                        new OA\Property(property: 'unit_price', type: 'number', format: 'float', example: 12.50),
                        new OA\Property(property: 'issue_date', type: 'string', format: 'date', nullable: true, example: '2026-07-30'),
                        new OA\Property(property: 'attached_file', type: 'string', format: 'binary', nullable: true),
                        new OA\Property(property: '_method', type: 'string', example: 'PUT', description: 'Requerido para simular PUT via POST y soportar archivos multipart'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true)
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Orden actualizada correctamente'),
            new OA\Response(response: 404, description: 'Orden no encontrada'),
            new OA\Response(response: 422, description: 'Errores de validación'),
            new OA\Response(response: 401, description: 'No autorizado')
        ]
    )]
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        try {
            $request->validate([
                'order_number'  => ['required', 'string', 'max:50', 'unique:purchase_orders,order_number,' . $purchaseOrder->id],
                'unit_price'    => ['required', 'numeric', 'min:0'],
                'issue_date'    => ['nullable', 'date'],
                'attached_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // max 5MB
                'is_active'     => ['required', 'boolean'],
            ], [
                'order_number.required' => 'El número de orden es obligatorio.',
                'order_number.unique'   => 'El número de orden ya está registrado.',
                'unit_price.required'   => 'El precio unitario es obligatorio.',
                'unit_price.numeric'    => 'El precio unitario debe ser un número.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'mensaje' => 'Errores de validación',
                'errors'  => $e->errors()
            ], 422);
        }

        $purchaseOrder->order_number = $request->order_number;
        $purchaseOrder->unit_price   = $request->unit_price;
        $purchaseOrder->issue_date   = $request->issue_date;
        $purchaseOrder->is_active    = $request->is_active;

        if ($request->hasFile('attached_file')) {
            if ($purchaseOrder->attached_file) {
                Storage::disk('public')->delete($purchaseOrder->attached_file);
            }
            $path = $request->file('attached_file')->store('purchase_orders', 'public');
            $purchaseOrder->attached_file = $path;
        }

        $purchaseOrder->save();

        return response()->json([
            'mensaje' => 'Orden de compra actualizada correctamente',
            'purchase_order' => PurchaseOrderResource::make($purchaseOrder),
        ], 200);
    }
}
