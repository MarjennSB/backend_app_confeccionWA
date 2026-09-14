<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserCollection;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use OpenApi\Attributes as OA;

class ApiUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.verify');
        $this->middleware('can:listar_usuario')->only('index');
        $this->middleware('can:registrar_usuario')->only('store');
        $this->middleware('can:editar_usuario')->only('update');
    }

    #[OA\Get(
        path: '/api/usuarios',
        summary: 'Listar usuarios',
        description: 'Obtiene el listado paginado de usuarios con sus datos completos y roles.',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                description: 'Buscar por nombres, apellidos, documento o correo.',
                schema: new OA\Schema(type: 'string', example: 'Juan')
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
            new OA\Response(response: 200, description: 'Usuarios obtenidos correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 500, description: 'Error interno del servidor')
        ]
    )]
    public function index(Request $request)
    {
        $search   = $request->string('search');
        $per_page = $request->integer('per_page', 10);

        $usuarios = User::with([
            'documentType', 
            'gender', 
            'roles'
        ])
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('last_name_father', 'like', '%' . $search . '%')
                  ->orWhere('last_name_mother', 'like', '%' . $search . '%')
                  ->orWhere('document_number', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            })
            ->orderBy('id', 'desc')
            ->paginate($per_page);

        return response()->json([
            'usuarios'   => UserCollection::make($usuarios),
            'pagination' => [
                'total'        => $usuarios->total(),
                'current_page' => $usuarios->currentPage(),
                'last_page'    => $usuarios->lastPage(),
                'per_page'     => $usuarios->perPage(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/usuarios',
        summary: 'Registrar usuario',
        description: 'Crea un nuevo usuario con todos sus datos y un rol.',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['password', 'is_active'],
                    properties: [
                        new OA\Property(property: 'document_type_id', type: 'integer', nullable: true, example: 1),
                        new OA\Property(property: 'document_number', type: 'string', nullable: true, example: '12345678'),
                        new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Juan Carlos'),
                        new OA\Property(property: 'last_name_father', type: 'string', nullable: true, example: 'Perez'),
                        new OA\Property(property: 'last_name_mother', type: 'string', nullable: true, example: 'Gomez'),
                        new OA\Property(property: 'gender_id', type: 'integer', nullable: true, example: 1),
                        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'juan@correo.com'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', example: '123456'),
                        new OA\Property(property: 'rol_id', type: 'integer', nullable: true, example: 2),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true, description: 'true activo, false inactivo')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Usuario creado correctamente'),
            new OA\Response(response: 422, description: 'Errores de validación'),
            new OA\Response(response: 401, description: 'No autorizado')
        ]
    )]
    public function store(Request $request)
    {
        try {
            $request->validate([
                'document_type_id' => ['nullable', 'integer', 'exists:document_types,id'],
                'document_number'  => ['nullable', 'string', 'max:60', 'unique:users,document_number'],
                'name'       => ['nullable', 'string', 'max:100'],
                'last_name_father' => ['nullable', 'string', 'max:60'],
                'last_name_mother' => ['nullable', 'string', 'max:60'],
                'gender_id'        => ['nullable', 'integer', 'exists:genders,id'],
                'email'            => ['nullable', 'string', 'email', 'max:150', 'unique:users,email'],
                'password'         => ['required', 'string', 'min:6'],
                'rol_id'           => ['nullable', 'integer', 'exists:roles,id'],
                'is_active'        => ['required', 'boolean'],
                'image_url'        => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            ], [
                'document_number.unique' => 'El número de documento ya está registrado.',
                'email.email'            => 'El correo no tiene un formato válido.',
                'email.unique'           => 'El correo ya está registrado.',
                'password.required'      => 'La contraseña es obligatoria.',
                'password.min'           => 'La contraseña debe tener al menos 6 caracteres.',
                'rol_id.exists'          => 'El rol seleccionado no existe.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'mensaje' => 'Errores de validación',
                'errors'  => $e->errors()
            ], 422);
        }

        $user = new User();
        $user->document_type_id = $request->document_type_id;
        $user->document_number  = $request->document_number;
        $user->name             = $request->name;
        $user->last_name_father = $request->last_name_father;
        $user->last_name_mother = $request->last_name_mother;
        $user->gender_id        = $request->gender_id;
        $user->email            = $request->email;
        $user->password         = Hash::make($request->password);
        $user->is_active        = $request->is_active;

        if ($request->hasFile('image_url')) {
            $path = $request->file('image_url')->store('users', 'public');
            $user->image_url = $path;
        }

        $user->save();

        if ($request->rol_id) {
            $user->assignRole($request->rol_id);
        }

        return response()->json([
            'codigo'  => 200,
            'mensaje' => 'Usuario creado correctamente',
            'usuario' => UserResource::make($user),
        ], 200);
    }

    #[OA\Put(
        path: '/api/usuarios/{id}',
        summary: 'Actualizar usuario',
        description: 'Actualiza la información completa de un usuario y su rol.',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID del usuario',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['is_active'],
                    properties: [
                        new OA\Property(property: 'document_type_id', type: 'integer', nullable: true, example: 1),
                        new OA\Property(property: 'document_number', type: 'string', nullable: true, example: '12345678'),
                        new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Juan Carlos'),
                        new OA\Property(property: 'last_name_father', type: 'string', nullable: true, example: 'Perez'),
                        new OA\Property(property: 'last_name_mother', type: 'string', nullable: true, example: 'Gomez'),
                        new OA\Property(property: 'gender_id', type: 'integer', nullable: true, example: 1),
                        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'juan@correo.com'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', nullable: true, example: '123456'),
                        new OA\Property(property: 'rol_id', type: 'integer', nullable: true, example: 2),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true, description: 'true activo, false inactivo')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Usuario actualizado correctamente'),
            new OA\Response(response: 404, description: 'Usuario no encontrado'),
            new OA\Response(response: 422, description: 'Errores de validación'),
            new OA\Response(response: 401, description: 'No autorizado')
        ]
    )]
    public function update(Request $request, User $usuario)
    {
        try {
            $request->validate([
                'document_type_id' => ['nullable', 'integer', 'exists:document_types,id'],
                'document_number'  => ['nullable', 'string', 'max:60', 'unique:users,document_number,' . $usuario->id],
                'name'       => ['nullable', 'string', 'max:100'],
                'last_name_father' => ['nullable', 'string', 'max:60'],
                'last_name_mother' => ['nullable', 'string', 'max:60'],
                'gender_id'        => ['nullable', 'integer', 'exists:genders,id'],
                'email'            => ['nullable', 'string', 'email', 'max:150', 'unique:users,email,' . $usuario->id],
                'password'         => ['nullable', 'string', 'min:6'],
                'rol_id'           => ['nullable', 'integer', 'exists:roles,id'],
                'is_active'        => ['required', 'boolean'],
                'image_url'        => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            ], [
                'document_number.unique' => 'El número de documento ya está registrado.',
                'email.email'            => 'El correo no tiene un formato válido.',
                'email.unique'           => 'El correo ya está registrado.',
                'password.min'           => 'La contraseña debe tener al menos 6 caracteres.',
                'rol_id.exists'          => 'El rol seleccionado no existe.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'mensaje' => 'Errores de validación',
                'errors'  => $e->errors()
            ], 422);
        }

        $usuario->document_type_id = $request->document_type_id;
        $usuario->document_number  = $request->document_number;
        $usuario->name             = $request->name;
        $usuario->last_name_father = $request->last_name_father;
        $usuario->last_name_mother = $request->last_name_mother;
        $usuario->gender_id        = $request->gender_id;
        $usuario->email            = $request->email;
        $usuario->is_active        = $request->is_active;

        if ($request->filled('password')) {
            $usuario->password = Hash::make($request->password);
        }

        if ($request->hasFile('image_url')) {
            if ($usuario->image_url) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($usuario->image_url);
            }
            $path = $request->file('image_url')->store('users', 'public');
            $usuario->image_url = $path;
        }

        $usuario->save();

        if ($request->rol_id) {
            $usuario->syncRoles([$request->rol_id]);
        }

        return response()->json([
            'mensaje' => 'Usuario actualizado correctamente',
            'usuario' => UserResource::make($usuario),
        ], 200);
    }
}