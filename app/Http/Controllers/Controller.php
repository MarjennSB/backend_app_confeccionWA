<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'API Confecciones WA',
    description: 'Documentación de la API para el sistema de Confecciones WA'
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'Servidor Local'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
abstract class Controller extends \Illuminate\Routing\Controller
{
    //
}
