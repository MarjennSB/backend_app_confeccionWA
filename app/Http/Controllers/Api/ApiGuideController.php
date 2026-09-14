<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Guide\GuideCollection;
use App\Http\Resources\Guide\GuideResource;
use App\Models\Guide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class ApiGuideController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.verify');
    }

    #[OA\Get(
        path: '/api/guides',
        summary: 'Listar guías',
        description: 'Obtiene el listado paginado de guías.',
        tags: ['Guides'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                description: 'Buscar por número de guía.',
                schema: new OA\Schema(type: 'string', example: 'GUI-001')
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
            new OA\Response(response: 200, description: 'Guías obtenidas correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 500, description: 'Error interno del servidor')
        ]
    )]
    public function index(Request $request)
    {
        $search   = $request->string('search');
        $per_page = $request->integer('per_page', 10);

        $guides = Guide::where(function ($q) use ($search) {
                $q->where('guide_number', 'like', '%' . $search . '%');
            })
            ->orderBy('id', 'desc')
            ->paginate($per_page);

        return response()->json([
            'guides'     => GuideCollection::make($guides),
            'pagination' => [
                'total'        => $guides->total(),
                'current_page' => $guides->currentPage(),
                'last_page'    => $guides->lastPage(),
                'per_page'     => $guides->perPage(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/guides',
        summary: 'Registrar guía',
        description: 'Crea una nueva guía.',
        tags: ['Guides'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['guide_number', 'attached_file', 'is_active'],
                    properties: [
                        new OA\Property(property: 'guide_number', type: 'string', example: 'GUI-001'),
                        new OA\Property(property: 'issue_date', type: 'string', format: 'date', nullable: true, example: '2026-07-30'),
                        new OA\Property(property: 'attached_file', type: 'string', format: 'binary', description: 'Archivo adjunto obligatorio'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true)
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Guía creada correctamente'),
            new OA\Response(response: 422, description: 'Errores de validación'),
            new OA\Response(response: 401, description: 'No autorizado')
        ]
    )]
    public function store(Request $request)
    {
        try {
            $request->validate([
                'guide_number'  => ['required', 'string', 'max:50', 'unique:guides,guide_number'],
                'issue_date'    => ['nullable', 'date'],
                'attached_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
                'is_active'     => ['required', 'boolean'],
            ], [
                'guide_number.required'  => 'El número de guía es obligatorio.',
                'guide_number.unique'    => 'El número de guía ya está registrado.',
                'attached_file.required' => 'El archivo adjunto es obligatorio.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'mensaje' => 'Errores de validación',
                'errors'  => $e->errors()
            ], 422);
        }

        $guide = new Guide();
        $guide->guide_number = $request->guide_number;
        $guide->issue_date   = $request->issue_date;
        $guide->is_active    = $request->is_active;

        $path = $request->file('attached_file')->store('guides', 'public');
        $guide->attached_file = $path;

        $guide->save();

        return response()->json([
            'codigo'  => 200,
            'mensaje' => 'Guía creada correctamente',
            'guide'   => GuideResource::make($guide),
        ], 200);
    }

    #[OA\Post(
        path: '/api/guides/{id}',
        summary: 'Actualizar guía',
        description: 'Actualiza la información de una guía. Usa POST enviando _method=PUT debido al soporte de envío de archivos.',
        tags: ['Guides'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID de la guía',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['guide_number', 'is_active', '_method'],
                    properties: [
                        new OA\Property(property: 'guide_number', type: 'string', example: 'GUI-001'),
                        new OA\Property(property: 'issue_date', type: 'string', format: 'date', nullable: true, example: '2026-07-30'),
                        new OA\Property(property: 'attached_file', type: 'string', format: 'binary', nullable: true, description: 'Archivo adjunto opcional para reemplazar el existente'),
                        new OA\Property(property: '_method', type: 'string', example: 'PUT', description: 'Requerido para simular PUT via POST y soportar archivos multipart'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true)
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Guía actualizada correctamente'),
            new OA\Response(response: 404, description: 'Guía no encontrada'),
            new OA\Response(response: 422, description: 'Errores de validación'),
            new OA\Response(response: 401, description: 'No autorizado')
        ]
    )]
    public function update(Request $request, Guide $guide)
    {
        try {
            $request->validate([
                'guide_number'  => ['required', 'string', 'max:50', 'unique:guides,guide_number,' . $guide->id],
                'issue_date'    => ['nullable', 'date'],
                'attached_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // Opcional en update
                'is_active'     => ['required', 'boolean'],
            ], [
                'guide_number.required' => 'El número de guía es obligatorio.',
                'guide_number.unique'   => 'El número de guía ya está registrado.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'mensaje' => 'Errores de validación',
                'errors'  => $e->errors()
            ], 422);
        }

        $guide->guide_number = $request->guide_number;
        $guide->issue_date   = $request->issue_date;
        $guide->is_active    = $request->is_active;

        if ($request->hasFile('attached_file')) {
            if ($guide->attached_file) {
                Storage::disk('public')->delete($guide->attached_file);
            }
            $path = $request->file('attached_file')->store('guides', 'public');
            $guide->attached_file = $path;
        }

        $guide->save();

        return response()->json([
            'mensaje' => 'Guía actualizada correctamente',
            'guide'   => GuideResource::make($guide),
        ], 200);
    }
}
