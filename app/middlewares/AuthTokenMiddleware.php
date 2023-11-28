<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

require_once './utils/AutentificadorJWT.php';

class AuthTokenMiddleware
{
    private function verificarYObtenerData($token)
    {
        try {
            AutentificadorJWT::VerificarToken($token);
            return AutentificadorJWT::ObtenerData($token);
        } catch (Exception $e) {
            return null;
        }
    }

    private function validarPermisos(Request $request, RequestHandler $handler, $rolesPermitidos = []): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');
        $mensaje = 'ok';

        if (strpos($header, 'Bearer') !== false) {
            $token = trim(explode("Bearer", $header)[1]);
            $jsonData = $this->verificarYObtenerData($token);

            if ($jsonData !== null) {
                $rol = $jsonData->rol;
                if (!empty($rolesPermitidos)) {
                    if (!in_array(strtolower($rol), $rolesPermitidos)) {
                        $mensaje = 'No puedes hacer esta accion, solo pueden los usuarios con el rol: ' . implode(', ', $rolesPermitidos) . '.';
                    }
                }
            } else {
                $mensaje = 'El token no es valido.';
            }
        } else {
            $mensaje = 'Formato de token invalido en el encabezado de autorizacion.';
        }

        if ($mensaje === 'ok') {
            return $handler->handle($request);
        } else {
            $response = new Response();
            $payload = json_encode(['mensaje' => $mensaje]);
            $response->getBody()->write($payload);
            return $response;
        }
    }

    public function validarGerente(Request $request, RequestHandler $handler): ResponseInterface
    {
        return $this->validarPermisos($request, $handler, ['GERENTE']);
    }
    public function validarRecepcionistaOGerente(Request $request, RequestHandler $handler): ResponseInterface
    {
        return $this->validarPermisos($request, $handler, ['RECEPCIONISTA', 'GERENTE']);
    }
    public function validarRecepcionistaOCliente(Request $request, RequestHandler $handler): ResponseInterface
    {
        return $this->validarPermisos($request, $handler, ['RECEPCIONISTA', 'CLIENTE']);
    }
    public function validarRol(Request $request, RequestHandler $handler): ResponseInterface
    {
        return $this->validarPermisos($request, $handler, ['GERENTE', 'RECEPCIONISTA', 'CLIENTE']);
    }
}