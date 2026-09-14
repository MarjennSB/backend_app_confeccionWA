<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Invoice\InvoiceCollection;
use App\Http\Resources\Invoice\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class ApiInvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.verify');
    }

    #[OA\Get(
        path: '/api/invoices',
        summary: 'Listar facturas',
        description: 'Obtiene el listado paginado de facturas.',
        tags: ['Invoices'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                description: 'Buscar por número de factura.',
                schema: new OA\Schema(type: 'string', example: 'INV-001')
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
            new OA\Response(response: 200, description: 'Facturas obtenidas correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 500, description: 'Error interno del servidor')
        ]
    )]
    public function index(Request $request)
    {
        $search   = $request->string('search');
        $per_page = $request->integer('per_page', 10);

        $invoices = Invoice::where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', '%' . $search . '%');
            })
            ->orderBy('id', 'desc')
            ->paginate($per_page);

        return response()->json([
            'invoices'   => InvoiceCollection::make($invoices),
            'pagination' => [
                'total'        => $invoices->total(),
                'current_page' => $invoices->currentPage(),
                'last_page'    => $invoices->lastPage(),
                'per_page'     => $invoices->perPage(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/invoices',
        summary: 'Registrar factura',
        description: 'Crea una nueva factura.',
        tags: ['Invoices'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['invoice_number', 'total_amount', 'issue_date', 'is_active'],
                    properties: [
                        new OA\Property(property: 'production_id', type: 'integer', nullable: true, example: 1),
                        new OA\Property(property: 'invoice_number', type: 'string', example: 'INV-001'),
                        new OA\Property(property: 'total_amount', type: 'number', format: 'float', example: 1500.50),
                        new OA\Property(property: 'currency', type: 'string', example: 'USD', description: 'USD o PEN'),
                        new OA\Property(property: 'issue_date', type: 'string', format: 'date', example: '2026-07-30'),
                        new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true, example: '2026-08-30'),
                        new OA\Property(property: 'payment_status', type: 'string', example: 'PENDIENTE', description: 'PENDIENTE, PAGADA, ANULADA'),
                        new OA\Property(property: 'attached_file', type: 'string', format: 'binary', nullable: true),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true)
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Factura creada correctamente'),
            new OA\Response(response: 422, description: 'Errores de validación'),
            new OA\Response(response: 401, description: 'No autorizado')
        ]
    )]
    public function store(Request $request)
    {
        try {
            $request->validate([
                'production_id'  => ['nullable', 'integer', 'exists:production,id', 'unique:invoices,production_id'],
                'invoice_number' => ['required', 'string', 'max:50', 'unique:invoices,invoice_number'],
                'total_amount'   => ['required', 'numeric', 'min:0'],
                'currency'       => ['nullable', 'string', 'max:3', 'in:USD,PEN'],
                'issue_date'     => ['required', 'date'],
                'due_date'       => ['nullable', 'date'],
                'payment_status' => ['nullable', 'string', 'in:PENDIENTE,PAGADA,ANULADA'],
                'attached_file'  => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
                'is_active'      => ['required', 'boolean'],
            ], [
                'invoice_number.required' => 'El número de factura es obligatorio.',
                'invoice_number.unique'   => 'El número de factura ya está registrado.',
                'total_amount.required'   => 'El monto total es obligatorio.',
                'total_amount.numeric'    => 'El monto total debe ser un número.',
                'issue_date.required'     => 'La fecha de emisión es obligatoria.',
                'production_id.unique'    => 'La producción seleccionada ya tiene una factura asociada.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'mensaje' => 'Errores de validación',
                'errors'  => $e->errors()
            ], 422);
        }

        $invoice = new Invoice();
        $invoice->production_id  = $request->production_id;
        $invoice->invoice_number = $request->invoice_number;
        $invoice->total_amount   = $request->total_amount;
        $invoice->currency       = $request->currency ?? 'USD';
        $invoice->issue_date     = $request->issue_date;
        $invoice->due_date       = $request->due_date;
        $invoice->payment_status = $request->payment_status ?? 'PENDIENTE';
        $invoice->is_active      = $request->is_active;

        if ($request->hasFile('attached_file')) {
            $path = $request->file('attached_file')->store('invoices', 'public');
            $invoice->attached_file = $path;
        }

        $invoice->save();

        return response()->json([
            'codigo'  => 200,
            'mensaje' => 'Factura creada correctamente',
            'invoice' => InvoiceResource::make($invoice),
        ], 200);
    }

    #[OA\Post(
        path: '/api/invoices/{id}',
        summary: 'Actualizar factura',
        description: 'Actualiza la información de una factura. Usa POST enviando _method=PUT debido al soporte de envío de archivos.',
        tags: ['Invoices'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID de la factura',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['invoice_number', 'total_amount', 'issue_date', 'is_active', '_method'],
                    properties: [
                        new OA\Property(property: 'production_id', type: 'integer', nullable: true, example: 1),
                        new OA\Property(property: 'invoice_number', type: 'string', example: 'INV-001'),
                        new OA\Property(property: 'total_amount', type: 'number', format: 'float', example: 1500.50),
                        new OA\Property(property: 'currency', type: 'string', example: 'USD', description: 'USD o PEN'),
                        new OA\Property(property: 'issue_date', type: 'string', format: 'date', example: '2026-07-30'),
                        new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true, example: '2026-08-30'),
                        new OA\Property(property: 'payment_status', type: 'string', example: 'PENDIENTE', description: 'PENDIENTE, PAGADA, ANULADA'),
                        new OA\Property(property: 'attached_file', type: 'string', format: 'binary', nullable: true),
                        new OA\Property(property: '_method', type: 'string', example: 'PUT', description: 'Requerido para simular PUT via POST'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true)
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Factura actualizada correctamente'),
            new OA\Response(response: 404, description: 'Factura no encontrada'),
            new OA\Response(response: 422, description: 'Errores de validación'),
            new OA\Response(response: 401, description: 'No autorizado')
        ]
    )]
    public function update(Request $request, Invoice $invoice)
    {
        try {
            $request->validate([
                'production_id'  => ['nullable', 'integer', 'exists:production,id', 'unique:invoices,production_id,' . $invoice->id],
                'invoice_number' => ['required', 'string', 'max:50', 'unique:invoices,invoice_number,' . $invoice->id],
                'total_amount'   => ['required', 'numeric', 'min:0'],
                'currency'       => ['nullable', 'string', 'max:3', 'in:USD,PEN'],
                'issue_date'     => ['required', 'date'],
                'due_date'       => ['nullable', 'date'],
                'payment_status' => ['nullable', 'string', 'in:PENDIENTE,PAGADA,ANULADA'],
                'attached_file'  => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
                'is_active'      => ['required', 'boolean'],
            ], [
                'invoice_number.required' => 'El número de factura es obligatorio.',
                'invoice_number.unique'   => 'El número de factura ya está registrado.',
                'total_amount.required'   => 'El monto total es obligatorio.',
                'total_amount.numeric'    => 'El monto total debe ser un número.',
                'issue_date.required'     => 'La fecha de emisión es obligatoria.',
                'production_id.unique'    => 'La producción seleccionada ya tiene una factura asociada.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'mensaje' => 'Errores de validación',
                'errors'  => $e->errors()
            ], 422);
        }

        $invoice->production_id  = $request->production_id;
        $invoice->invoice_number = $request->invoice_number;
        $invoice->total_amount   = $request->total_amount;
        
        if ($request->has('currency')) {
            $invoice->currency = $request->currency;
        }
        
        $invoice->issue_date     = $request->issue_date;
        $invoice->due_date       = $request->due_date;

        if ($request->has('payment_status')) {
            $invoice->payment_status = $request->payment_status;
        }
        
        $invoice->is_active      = $request->is_active;

        if ($request->hasFile('attached_file')) {
            if ($invoice->attached_file) {
                Storage::disk('public')->delete($invoice->attached_file);
            }
            $path = $request->file('attached_file')->store('invoices', 'public');
            $invoice->attached_file = $path;
        }

        $invoice->save();

        return response()->json([
            'mensaje' => 'Factura actualizada correctamente',
            'invoice' => InvoiceResource::make($invoice),
        ], 200);
    }
}
