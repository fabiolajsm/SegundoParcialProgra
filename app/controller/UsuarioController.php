<?php
use \Slim\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

require './utils/AutentificadorJWT.php';

class UsuarioController
{
    private $usuarioDAO;

    public function __construct($usuarioDAO)
    {
        $this->usuarioDAO = $usuarioDAO;
    }
    public function login(ServerRequest $request, ResponseInterface $response)
    {
        try {
            $parametros = $request->getQueryParams();
            $usuario = $parametros['usuario'] ?? null;
            $contrasena = $parametros['contrasena'] ?? null;

            if ($usuario == null || $contrasena == null || empty($usuario) || empty($contrasena)) {
                return $response->withStatus(404)->withJson(['error' => 'Debe ingresar el  del usuario y contrasena.']);
            }
            $usuarioEncontrado = $this->usuarioDAO->login($usuario, $contrasena);
            if ($usuarioEncontrado) {
                $datos = array('usuario' => $usuario, 'cargoEmpleado' => $usuarioEncontrado['tipo']);
                $token = AutentificadorJWT::CrearToken($datos);
                $payload = array('jwt' => $token);
                return $response->withStatus(200)->withJson($payload);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontraron usuarios']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
    public function altaUsuario(ServerRequest $request, ResponseInterface $response)
    {
        $data = $request->getParsedBody();

        $usuario = $data['usuario'] ?? "";
        $password = $data['password'] ?? "";
        $rol = $data['rol'] ?? "";

        $rolesPermitidos = ['GERENTE', 'RECEPCIONISTA', 'CLIENTE'];

        if (empty($usuario) || empty($password) || empty($rol)) {
            return $response->withStatus(400)->withJson(['error' => 'Completar datos obligatorios: usuario, password, y rol.']);
        }
        $usuario = strtoupper($usuario);
        if ($this->usuarioDAO->obtenerUsuario($usuario)) {
            return $response->withStatus(400)->withJson(['error' => 'Ya existe el usuario: ' . $usuario]);
        }
        $rol = strtoupper($rol);
        if (!in_array($rol, $rolesPermitidos)) {
            return $response->withStatus(400)->withJson(['error' => 'Rol del usuario incorrecto. Debe ser de tipo: Gerente, Recepcionista o Cliente.']);
        }

        $idUsuario = $this->usuarioDAO->crearUsuario($usuario, $password, $rol);
        if ($idUsuario) {
            return $response->withStatus(201)->withJson(['mensaje' => 'Usuario creado', 'id' => $idUsuario]);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo crear el usuario']);
        }
    }

    // Listados
    public function listarUsuarios(ServerRequest $request, ResponseInterface $response)
    {
        try {
            $usuarios = $this->usuarioDAO->obtenerUsuarios();
            if ($usuarios) {
                return $response->withStatus(200)->withJson($usuarios);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontraron usuarios']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
}