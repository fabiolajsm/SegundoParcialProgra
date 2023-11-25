<?php
use Slim\Routing\RouteCollectorProxy;

// Error Handling
error_reporting(-1);
ini_set('display_errors', 1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

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

//$pdo = new PDO('mysql:host=localhost;dbname=segundoparcial;charset=utf8', 'root', '', array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));


// $usuarioDAO = new UsuarioDAO($pdo);
// $usuarioController = new UsuarioController($usuarioDAO);
// // Grupo de rutas para usuarios
// $app->group('/usuarios', function (RouteCollectorProxy $group) use ($usuarioController) {
//     $group->get('[/]', [$usuarioController, 'listarUsuarios'])->add(\AuthTokenMiddleware::class . ":validarSocio");
//     $group->get('/traerUno', [$usuarioController, 'listarUsuarioPorId'])->add(\AuthTokenMiddleware::class . ":validarSocio");
//     $group->post('[/]', [$usuarioController, 'altaUsuario'])->add(\AuthTokenMiddleware::class . ":validarSocio");
//     $group->put('[/]', [$usuarioController, 'modificarUsuarioPorId'])->add(\AuthTokenMiddleware::class . ":validarSocio");
//     $group->get('/borrar', [$usuarioController, 'borrarUsuarioPorId'])->add(\AuthTokenMiddleware::class . ":validarSocio");
//     $group->get('/login', [$usuarioController, 'login']);
// });


$app->run();
?>