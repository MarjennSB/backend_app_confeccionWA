<?php

namespace App\Http\Controllers;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\DocumentType;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.verify', ['except' => ['login', 'register']]);
    }
    
    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Iniciar sesión',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'admin@correo.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: '123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Token generado correctamente'),
            new OA\Response(response: 401, description: 'Credenciales no válidas'),
            new OA\Response(response: 500, description: 'Error al procesar el token')
        ]
    )]
    
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (! $token = $this->guard()->attempt($credentials)) {
                return response()->json(['error' => 'Credenciales no validas'], 401);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ], 500);
        }

        return $this->respondWithToken($token);
    }

    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Registrar nuevo cliente',
        description: 'Endpoint público para que los clientes se registren en la tienda virtual completando todos sus datos. Automáticamente asigna el rol de USUARIO EXTERNO y devuelve el token de sesión.',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'last_name_father', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'document_type_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'document_number', type: 'string', nullable: true, example: '12345678'),
                    new OA\Property(property: 'name', type: 'string', example: 'Juan Carlos'),
                    new OA\Property(property: 'last_name_father', type: 'string', example: 'Perez'),
                    new OA\Property(property: 'last_name_mother', type: 'string', nullable: true, example: 'Gomez'),
                    new OA\Property(property: 'gender_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan@correo.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: '123456'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuario registrado y autenticado correctamente'
            ),
            new OA\Response(
                response: 422,
                description: 'Errores de validación'
            )
        ]
    )]
    public function register(Request $request)
    {
        try {
            $request->validate([
                'document_type_id' => ['nullable', 'integer', 'exists:document_types,id'],
                'document_number'  => ['nullable', 'string', 'max:60', 'unique:users,document_number'],
                'name'       => ['required', 'string', 'max:100'],
                'last_name_father' => ['required', 'string', 'max:60'],
                'last_name_mother' => ['nullable', 'string', 'max:60'],
                'gender_id'        => ['nullable', 'integer', 'exists:genders,id'],
                'email'            => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
                'password'         => ['required', 'string', 'min:6'],
            ], [
                'document_number.unique' => 'El número de documento ya está registrado.',
                'name.required'    => 'El nombre es obligatorio.',
                'last_name_father.required'=> 'El apellido paterno es obligatorio.',
                'email.required'         => 'El correo es obligatorio.',
                'email.email'            => 'El formato del correo es inválido.',
                'email.unique'           => 'Este correo ya está registrado.',
                'password.required'      => 'La contraseña es obligatoria.',
                'password.min'           => 'La contraseña debe tener al menos 6 caracteres.',
            ]);

            // Custom validation for document_number length
            if ($request->filled('document_type_id') && $request->filled('document_number')) {
                $tipoDoc = DocumentType::find($request->document_type_id);
                if ($tipoDoc) {
                    $length = strlen($request->document_number);
                    if ($tipoDoc->min_length === $tipoDoc->max_length && $length !== $tipoDoc->max_length) {
                        throw ValidationException::withMessages([
                            'document_number' => ["El número de documento para {$tipoDoc->name} debe tener exactamente {$tipoDoc->max_length} caracteres."]
                        ]);
                    } elseif ($length < $tipoDoc->min_length || $length > $tipoDoc->max_length) {
                        throw ValidationException::withMessages([
                            'document_number' => ["El número de documento para {$tipoDoc->name} debe tener entre {$tipoDoc->min_length} y {$tipoDoc->max_length} caracteres."]
                        ]);
                    }
                }
            }
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
        $user->is_active        = true;
        
        $user->save();

        // Asignar el rol automáticamente
        $role = Role::firstOrCreate(['name' => 'USUARIO EXTERNO', 'guard_name' => 'api']);
        $user->assignRole($role);

        // Iniciar sesión automáticamente
        $credentials = $request->only('email', 'password');
        $token = $this->guard()->attempt($credentials);

        return response()->json([
            'mensaje'      => 'Registro completado exitosamente.',
            'usuario'      => UserResource::make($user),
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => $this->guard()->getTTL() * 60,
        ], 200);
    }

    #[OA\Get(
        path: '/api/auth/me',
        summary: 'Obtener usuario autenticado',
        tags: ['Autenticación'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Información del usuario'
            ),
            new OA\Response(
                response: 401,
                description: 'No autorizado'
            ),
        ]
    )]

    public function me()
    {
        $user = $this->guard()->user();
        return response()->json([
            'estado' => true,
            'mensaje' => 'Se obtuvo la informacion exitosamente',
            'usuario' => UserResource::make($user),
        ]);
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Cerrar sesión',
        description: 'Invalida el token JWT del usuario autenticado.',
        tags: ['Autenticación'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sesión cerrada correctamente'
            ),
            new OA\Response(
                response: 401,
                description: 'Token inválido o no autorizado'
            ),
        ]
    )]

    public function logout()
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Se cerro satisfactoriamente la sesion']);
    }

    #[OA\Post(
        path: '/api/auth/refresh',
        summary: 'Renovar token',
        description: 'Genera un nuevo token JWT para el usuario autenticado.',
        tags: ['Autenticación'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Nuevo token generado correctamente'
            ),
            new OA\Response(
                response: 401,
                description: 'Token inválido o expirado'
            ),
        ]
    )]

    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    protected function respondWithToken(string $token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->getTTL() * 60,
        ]);
    }

    private function guard(): JWTGuard
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        return $guard;
    }
}