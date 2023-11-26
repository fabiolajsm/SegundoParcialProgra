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
    $group->get('[/]', [$clienteController, 'listarClientes']);
    $group->get('/traerUno', [$clienteController, 'consultarCliente']);
    $group->post('[/]', [$clienteController, 'crearCliente']);
    $group->delete('/borrarCliente', [$clienteController, 'borrarCliente']);
    $group->put('[/]', [$clienteController, 'modificarCliente']);
});

$reservaDAO = new ReservaDAO($pdo);
$reservaController = new ReservaController($reservaDAO);
// Grupo de rutas para reservas
$app->group('/reservas', function (RouteCollectorProxy $group) use ($reservaController) {
    $group->post('[/]', [$reservaController, 'crearReserva']);
    $group->get('[/]', [$reservaController, 'listarReservas']);
    $group->get('/traerUno', [$reservaController, 'consultarReserva']);
    $group->get('/consultarPorFecha', [$reservaController, 'consultarReservasPorFecha']);
    $group->get('/porTipoDeHabitacion', [$reservaController, 'listarReservasPorTipoHabitacion']);
    $group->get('/cancelacionesPorTipoClienteYFecha', [$reservaController, 'obtenerTotalCancelacionesPorTipoYFecha']);
    $group->get('/listarCancelacionesPorCliente', [$reservaController, 'listarCancelacionesPorCliente']);
    $group->get('/listarCancelacionesEntreFechas', [$reservaController, 'listarCancelacionesEntreFechas']);
    $group->get('/listarCancelacionesPorTipoCliente', [$reservaController, 'listarCancelacionesPorTipoCliente']);
    $group->get('/listarOperacionesPorCliente', [$reservaController, 'listarOperacionesPorCliente']);
    $group->get('/listarReservasPorModalidad', [$reservaController, 'listarReservasPorModalidad']);
});

$app->run();
?>