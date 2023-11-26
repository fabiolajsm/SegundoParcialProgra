<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface;

require_once './clases/ManejadorArchivos.php';

class ClienteController
{
    private $clienteDAO;
    private $manejadorArchivos;

    public function __construct($clienteDAO)
    {
        $this->manejadorArchivos = new ManejadorArchivos();
        $this->clienteDAO = $clienteDAO;
    }

    public function crearCliente(Request $request, ResponseInterface $response)
    {
        $data = $request->getParsedBody();
        $nombre = $data['nombre'] ?? "";
        $apellido = $data['apellido'] ?? "";
        $tipoDocumento = $data['tipoDocumento'] ?? "";
        $nroDocumento = $data['nroDocumento'] ?? "";
        $tipo = $data['tipo'] ?? "";
        $pais = $data['pais'] ?? "";
        $ciudad = $data['ciudad'] ?? "";
        $email = $data['email'] ?? "";
        $telefono = $data['telefono'] ?? "";
        $modalidadPago = $data['modalidadPago'] ?? "EFECTIVO";
        $fotoDelCliente = $_FILES['fotoDelCliente']['full_path'] ?? null;

        $tiposCliente = array('INDI', 'CORPO');
        $tiposDocumentos = array('DNI', 'LE', 'LC', 'PASAPORTE');
        $modalidadesDePago = array('EFECTIVO', 'TARJETA', 'MERCADO PAGO');

        if (empty($nombre) || empty($apellido) || empty($email) || empty($tipoDocumento) || empty($nroDocumento) || empty($tipo) || empty($pais) || empty($ciudad) || empty($telefono) || $fotoDelCliente == null) {
            return $response->withStatus(400)->withJson(['error' => 'Completar datos obligatorios: nombre, apellido, email, tipoDocumento, nroDocumento, tipo, pais, ciudad, fotoDelCliente y telefono.']);
        }
        if (!in_array($tipoDocumento, $tiposDocumentos)) {
            return $response->withStatus(400)->withJson(['error' => 'Tipo de documento incorrecto. Debe ser uno de: DNI, LE, LC, PASAPORTE.']);
        }
        $modalidadPago = strtoupper($modalidadPago);
        if (!in_array($modalidadPago, $modalidadesDePago)) {
            return $response->withStatus(400)->withJson(['error' => 'Modalidad de pago incorrecta. Debe ser una de: EFECTIVO, TARJETA, MERCADO PAGO.']);
        }
        $tipo = strtoupper($tipo);
        if (!in_array($tipo, $tiposCliente)) {
            return $response->withStatus(400)->withJson(['error' => 'Tipo de cliente incorrecto. Debe ser de tipo: INDI o CORPO.']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $response->withStatus(400)->withJson(['error' => 'Formato de correo electrónico inválido.']);
        }
        if (!is_numeric($nroDocumento)) {
            return $response->withStatus(400)->withJson(['error' => 'El numero de documento tiene que ser un numero valido.']);
        }
        if (!preg_match('/^[0-9]{10}$/', $telefono)) {
            return $response->withStatus(400)->withJson(['error' => 'Formato de numero de telefono invalido. Debe contener 10 digitos.']);
        }
        if ($fotoDelCliente !== null) {
            $imageType = $_FILES['fotoDelCliente']['type'];
            if (stripos($imageType, 'jpg') === false && stripos($imageType, 'jpeg') === false) {
                return $response->withStatus(400)->withJson(['error' => 'La foto del cliente debe ser JPG o JPEG valido.']);
            }
        }
        if ($this->clienteDAO->existeCliente($nroDocumento)) {
            return $response->withStatus(400)->withJson(['error' => 'Ya existe el cliente: ' . $nombre . ' ' . $apellido . ' y tipo ' . $tipo]);
        }
        $tipo = $tipo . '-' . $tipoDocumento;
        $idCliente = $this->clienteDAO->crearCliente($nombre, $apellido, $tipoDocumento, $nroDocumento, $tipo, $pais, $ciudad, $email, $telefono, $modalidadPago);
        if ($idCliente) {
            $imagenID = $idCliente . $tipo;
            $carpetaImagenes = './datos/ImagenesDeClientes/2023/';
            $rutaImagen = $carpetaImagenes . strtoupper($imagenID) . '.jpg';
            $mensajeExito = "Cliente registrado exitosamente, pero hubo un problema al guardar la imagen.";
            if ($this->manejadorArchivos->subirImagen($rutaImagen)) {
                $mensajeExito = "Cliente registrado exitosamente.";
            }
            return $response->withStatus(201)->withJson(['mensaje' => $mensajeExito]);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo crear el cliente']);
        }
    }
    public function borrarCliente(Request $request, ResponseInterface $response)
    {
        try {
            $parametros = $request->getQueryParams();
            $nroCliente = $parametros['nroCliente'] ?? null;
            $tipoCliente = $parametros['tipoCliente'] ?? null;
            $tiposCliente = array('INDI', 'CORPO');

            if ($nroCliente == null || $tipoCliente == null) {
                return $response->withStatus(400)->withJson(['error' => 'Debe ingresar el número de cliente y el tipo del cliente para dar de baja.']);
            }
            $tipo = strtoupper($tipoCliente);
            if (!in_array($tipo, $tiposCliente)) {
                return $response->withStatus(400)->withJson(['error' => 'Tipo de cliente incorrecto. Debe ser de tipo: INDI o CORPO.']);
            }
            $cliente = $this->clienteDAO->obtenerCliente($nroCliente, $tipoCliente);
            if (!$cliente) {
                return $response->withStatus(404)->withJson(['error' => 'No se encontró el cliente para dar de baja.']);
            }

            $resultado = $this->clienteDAO->darDeBajaCliente($nroCliente);
            if (!$resultado) {
                return $response->withStatus(500)->withJson(['error' => 'Error al dar de baja el cliente.']);
            }
            $mensajeExito = 'Cliente borrado exitosamente, pero hubo un problema al guardar la imagen';
            $idFormateado = sprintf('%06d', $cliente['ID']);
            $nombreDeLaImagenCliente = $idFormateado . $cliente['tipo'];
            $imagenCliente = './datos/ImagenesDeClientes/2023/' . $nombreDeLaImagenCliente . '.jpg';
            $carpetaRespaldo = './ImagenesBackupClientes/2023/';
            if (file_exists($imagenCliente)) {
                if (!file_exists($carpetaRespaldo)) {
                    mkdir($carpetaRespaldo, 0777, true);
                }
                $nuevaRuta = $carpetaRespaldo . strtoupper($idFormateado) . 'ELIMINADO' . '.jpg';
                if (rename($imagenCliente, $nuevaRuta)) {
                    $mensajeExito = 'Cliente borrado exitosamente.';
                }
            }
            return $response->withStatus(500)->withJson(['mensaje' => $mensajeExito]);
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
    public function modificarCliente(Request $request, ResponseInterface $response)
    {
        try {
            $data = $request->getParsedBody();
            $idCliente = $data['idCliente'] ?? null;
            $nombre = $data['nombre'] ?? null;
            $apellido = $data['apellido'] ?? null;
            $tipoDocumento = $data['tipoDocumento'] ?? null;
            $nroDocumento = $data['nroDocumento'] ?? null;
            $tipo = $data['tipo'] ?? null;
            $pais = $data['pais'] ?? null;
            $ciudad = $data['ciudad'] ?? null;
            $email = $data['email'] ?? null;
            $telefono = $data['telefono'] ?? null;
            $modalidadPago = $data['modalidadPago'] ?? null;

            $tiposCliente = array('INDI', 'CORPO');
            $tiposDocumentos = array('DNI', 'LE', 'LC', 'PASAPORTE');
            $modalidadesDePago = array('EFECTIVO', 'TARJETA', 'MERCADO PAGO');

            if ($nombre === null && $apellido === null && $tipoDocumento === null && $nroDocumento === null && $tipo === null && $pais === null && $ciudad === null && $email === null && $telefono === null && $modalidadPago === null) {
                return $response->withStatus(400)->withJson(['error' => 'Debe ingresar al menos un campo para modificar.']);
            }
            if ($idCliente == null || $tipo == null) {
                return $response->withStatus(400)->withJson(['error' => 'Debe ingresar el idCliente y el tipo para poder modificarlo.']);
            }
            if ($tipoDocumento !== null) {
                $tipoDocumento = strtoupper($tipoDocumento);
                if (!in_array($tipoDocumento, $tiposDocumentos)) {
                    return $response->withStatus(400)->withJson(['error' => 'Tipo de documento incorrecto. Debe ser uno de: DNI, LE, LC, PASAPORTE.']);
                }
            }
            if ($modalidadPago !== null) {
                $modalidadPago = strtoupper($modalidadPago);
                if (!in_array($modalidadPago, $modalidadesDePago)) {
                    return $response->withStatus(400)->withJson(['error' => 'Modalidad de pago incorrecta. Debe ser una de: EFECTIVO, TARJETA, MERCADO PAGO.']);
                }
            }
            $tipo = strtoupper($tipo);
            if (!in_array($tipo, $tiposCliente)) {
                return $response->withStatus(400)->withJson(['error' => 'Tipo de cliente incorrecto. Debe ser de tipo: INDI o CORPO.']);
            }
            if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $response->withStatus(400)->withJson(['error' => 'Formato de correo electrónico inválido.']);
            }
            if ($nroDocumento !== null && !is_numeric($nroDocumento)) {
                return $response->withStatus(400)->withJson(['error' => 'El numero de documento tiene que ser un numero valido.']);
            }
            if ($telefono !== null && !preg_match('/^[0-9]{10}$/', $telefono)) {
                return $response->withStatus(400)->withJson(['error' => 'Formato de numero de telefono invalido. Debe contener 10 digitos.']);
            }

            $cliente = $this->clienteDAO->obtenerCliente($idCliente, $tipo);
            if (!$cliente) {
                return $response->withStatus(404)->withJson(['error' => 'No existe el cliente con el tipo y número de cliente proporcionados.']);
            }
            // Nuevos datos
            $nuevosDatos = [
                'ID' => sprintf('%06d', $cliente['ID']),
                'nombre' => $nombre ?? $cliente['nombre'],
                'apellido' => $apellido ?? $cliente['apellido'],
                'tipoDocumento' => $tipoDocumento ?? $cliente['tipoDocumento'],
                'nroDocumento' => $nroDocumento ?? $cliente['nroDocumento'],
                'tipo' => $tipo ?? $cliente['tipo'],
                'pais' => $pais ?? $cliente['pais'],
                'ciudad' => $ciudad ?? $cliente['ciudad'],
                'email' => $email ?? $cliente['email'],
                'telefono' => $telefono ?? $cliente['telefono'],
                'modalidadPago' => $modalidadPago ?? $cliente['modalidadPago'],
                'activo' => $cliente['activo'],
                'horaDeAlta' => $cliente['horaDeAlta'],
                'horaDeBaja' => $cliente['horaDeBaja'],
            ];

            $modificado = $this->clienteDAO->modificarCliente($idCliente, $nuevosDatos);
            if ($modificado) {
                return $response->withStatus(200)->withJson(['mensaje' => 'Cliente modificado exitosamente.']);
            } else {
                return $response->withStatus(500)->withJson(['error' => 'No se pudo modificar el cliente.']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
    public function listarClientes(Request $request, ResponseInterface $response)
    {
        try {
            $clientes = $this->clienteDAO->obtenerClientes();
            if ($clientes) {
                return $response->withStatus(200)->withJson($clientes);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontraron clientes']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
    public function consultarCliente(Request $request, ResponseInterface $response)
    {
        try {
            $parametros = $request->getQueryParams();
            $id = $parametros['id'] ?? null;
            $tipo = $parametros['tipo'] ?? null;
            $tiposCliente = array('INDI', 'CORPO');

            if ($id == null || $tipo == null) {
                return $response->withStatus(400)->withJson(['error' => 'Debe ingresar tanto el ID como el tipo del cliente que desea consultar.']);
            }

            $tipo = strtoupper($tipo);
            if (!in_array($tipo, $tiposCliente)) {
                return $response->withStatus(400)->withJson(['error' => 'Tipo de cliente incorrecto. Debe ser de tipo: INDI o CORPO.']);
            }

            $cliente = $this->clienteDAO->obtenerClientePorId($id);
            if ($cliente) {
                $tipoDelCliente = $tipo . '-' . $cliente['tipoDocumento'];
                if ($cliente['tipo'] !== $tipoDelCliente) {
                    return $response->withStatus(200)->withJson(['error', 'Tipo de cliente incorrecto']);
                }
                $datosCliente = [
                    'pais' => $cliente['pais'],
                    'ciudad' => $cliente['ciudad'],
                    'telefono' => $cliente['telefono'],
                ];
                return $response->withStatus(200)->withJson($datosCliente);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontró el cliente con el ID y tipo proporcionados.']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
}