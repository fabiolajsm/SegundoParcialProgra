<?php
use Slim\Routing\RouteCollectorProxy;

// Error Handling
error_reporting(-1);
ini_set('display_errors', 1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

require './dao/ClienteDAO.php';
require_once './controller/ClienteController.php';
require './dao/ReservaDAO.php';
require_once './controller/ReservaController.php';
require './dao/UsuarioDAO.php';
require_once './controller/UsuarioController.php';
require './middlewares/AuthTokenMiddleware.php';

// Instantiate App
$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add parse body
$app->addBodyParsingMiddleware();

// Routes
$app->get('[/]', function (Request $request, Response $response) {
    $payload = json_encode(array('method' => 'GET', 'msg' => "Segundo Parcial"));
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

$pdo = new PDO('mysql:host=localhost;dbname=segundoparcialprograiii;charset=utf8', 'root', '', array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));


$clienteDAO = new ClienteDAO($pdo);
$clienteController = new ClienteController($clienteDAO);
// Grupo de rutas para clientes
$app->group('/clientes', function (RouteCollectorProxy $group) use ($clienteController) {
    $group->get('[/]', [$clienteController, 'listarClientes'])->add(\AuthTokenMiddleware::class . ":validarRol");
    $group->get('/traerUno', [$clienteController, 'consultarCliente'])->add(\AuthTokenMiddleware::class . ":validarRol");
    $group->post('[/]', [$clienteController, 'crearCliente'])->add(\AuthTokenMiddleware::class . ":validarGerente");
    $group->delete('/borrarCliente', [$clienteController, 'borrarCliente'])->add(\AuthTokenMiddleware::class . ":validarGerente");
    $group->put('[/]', [$clienteController, 'modificarCliente'])->add(\AuthTokenMiddleware::class . ":validarRol");
});

$reservaDAO = new ReservaDAO($pdo);
$reservaController = new ReservaController($reservaDAO);
// Grupo de rutas para reservas
$app->group('/reservas', function (RouteCollectorProxy $group) use ($reservaController) {
    $group->post('[/]', [$reservaController, 'crearReserva'])->add(\AuthTokenMiddleware::class . ":validarRecepcionistaOGerente");
    $group->post('/cancelar', [$reservaController, 'cancelarReserva'])->add(\AuthTokenMiddleware::class . ":validarRecepcionistaOGerente");
    $group->post('/ajustar', [$reservaController, 'ajustarReserva'])->add(\AuthTokenMiddleware::class . ":validarRecepcionistaOGerente");
    $group->get('/ajustes', [$reservaController, 'obtenerAjustes'])->add(\AuthTokenMiddleware::class . ":validarRecepcionistaOGerente");
    $group->get('[/]', [$reservaController, 'listarReservas'])->add(\AuthTokenMiddleware::class . ":validarRecepcionistaOCliente");
    $group->get('/consultarPorFecha', [$reservaController, 'consultarReservasPorFecha'])->add(\AuthTokenMiddleware::class . ":validarRecepcionistaOCliente");
    $group->get('/traerUno', [$reservaController, 'consultarReserva'])->add(\AuthTokenMiddleware::class . ":validarRecepcionistaOCliente");
    $group->get('/listarReservasEntreFechas', [$reservaController, 'listarReservasEntreFechas'])->add(\AuthTokenMiddleware::class . ":validarRecepcionistaOCliente");
    $group->get('/porTipoDeHabitacion', [$reservaController, 'listarReservasPorTipoHabitacion'])->add(\AuthTokenMiddleware::class . ":validarRecepcionistaOCliente");
    $group->get('/cancelacionesPorTipoClienteYFecha', [$reservaController, 'obtenerTotalCancelacionesPorTipoYFecha'])->add(\AuthTokenMiddleware::class . ":validarRecepcionistaOCliente");
    $group->get('/listarCancelacionesPorCliente', [$reservaController, 'listarCancelacionesPorCliente'])->add(\AuthTokenMiddleware::class . ":validarRecepcionistaOCliente");
    $group->get('/listarCancelacionesEntreFechas', [$reservaController, 'listarCancelacionesEntreFechas'])->add(\AuthTokenMiddleware::class . ":validarRecepcionistaOCliente");
    $group->get('/listarCancelacionesPorTipoCliente', [$reservaController, 'listarCancelacionesPorTipoCliente'])->add(\AuthTokenMiddleware::class . ":validarRecepcionistaOCliente");
    $group->get('/listarOperacionesPorCliente', [$reservaController, 'listarOperacionesPorCliente'])->add(\AuthTokenMiddleware::class . ":validarRecepcionistaOCliente");
    $group->get('/listarReservasPorModalidad', [$reservaController, 'listarReservasPorModalidad'])->add(\AuthTokenMiddleware::class . ":validarRecepcionistaOCliente");
});

$usuarioDAO = new UsuarioDAO($pdo);
$usuarioController = new UsuarioController($usuarioDAO);
// Grupo de rutas para usuarios
$app->group('/usuarios', function (RouteCollectorProxy $group) use ($usuarioController) {
    $group->post('[/]', [$usuarioController, 'altaUsuario']);
    $group->get('/login', [$usuarioController, 'login']);
    $group->get('[/]', [$usuarioController, 'listarUsuarios']);
});

$app->run();
?>