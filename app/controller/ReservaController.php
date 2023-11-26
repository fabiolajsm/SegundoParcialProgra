<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface;

require_once './clases/ManejadorArchivos.php';

class ReservaController
{
    private $reservaDAO;
    private $manejadorArchivos;

    public function __construct($reservaDAO)
    {
        $this->manejadorArchivos = new ManejadorArchivos();
        $this->reservaDAO = $reservaDAO;
    }

    public function crearReserva(Request $request, ResponseInterface $response)
    {
        $data = $request->getParsedBody();
        $nroCliente = $data['nroCliente'] ?? null;
        $tipoCliente = $data['tipoCliente'] ?? null;
        $fechaEntrada = $data['fechaEntrada'] ?? null;
        $fechaSalida = $data['fechaSalida'] ?? null;
        $tipoHabitacion = $data['tipoHabitacion'] ?? null;
        $importeTotal = $data['importeTotal'] ?? null;

        $tiposCliente = array('INDI', 'CORPO');
        $tiposHabitacion = array('SIMPLE', 'DOBLE', 'SUITE');

        if ($nroCliente == null || $tipoCliente == null || $fechaEntrada == null || $fechaSalida == null || $tipoHabitacion == null || $importeTotal == null) {
            return $response->withStatus(400)->withJson(['error' => 'Completar datos obligatorios: nroCliente, tipoCliente, fechaEntrada, fechaSalida, tipoHabitacion, importeTotal.']);
        }
        $tipoCliente = strtoupper($tipoCliente);
        if (!in_array($tipoCliente, $tiposCliente)) {
            return $response->withStatus(400)->withJson(['error' => 'Tipo de cliente incorrecto. Debe ser de tipo: INDI o CORPO.']);
        }
        $tipoHabitacion = strtoupper($tipoHabitacion);
        if (!in_array($tipoHabitacion, $tiposHabitacion)) {
            return $response->withStatus(400)->withJson(['error' => 'Tipo de habitacion incorrecto. Debe ser de tipo: SIMPLE, DOBLE o SUITE.']);
        }
        if (!is_numeric($importeTotal) || $importeTotal < 1) {
            return $response->withStatus(400)->withJson(['error' => 'El importe debe ser un número válido mayor a cero.']);
        }

        try {
            $fechaEntradaObj = new DateTime($fechaEntrada);
            $fechaSalidaObj = new DateTime($fechaSalida);
        } catch (Exception $e) {
            return $response->withStatus(400)->withJson(['error' => 'Error: Las fechas proporcionadas no son válidas.']);
        }

        $fechaEntradaFormateada = $fechaEntradaObj->format('Y-m-d');
        $fechaSalidaFormateada = $fechaSalidaObj->format('Y-m-d');

        if (!$this->reservaDAO->obtenerClientePorIdyTipo($nroCliente, $tipoCliente)) {
            return $response->withStatus(400)->withJson(['error' => 'El cliente no ha sido encontrado.']);
        }

        $idReserva = $this->reservaDAO->crearReserva($nroCliente, $tipoCliente, $fechaEntradaFormateada, $fechaSalidaFormateada, $tipoHabitacion, $importeTotal);
        if ($idReserva) {
            $mensajeExito = "Reserva registrada exitosamente, pero hubo un problema al guardar la imagen.";
            $imagenOrigen = 'reservaExitosa.jpg';
            $carpetaDestino = './datos/ImagenesDeReservas2023';
            $nuevoNombre = $tipoCliente . strval($nroCliente) . strval($idReserva) . ".jpg";
            $rutaCompletaDestino = $carpetaDestino . '/' . strtoupper($nuevoNombre);
            if (!file_exists($carpetaDestino)) {
                mkdir($carpetaDestino, 0777, true);
            }
            if (copy($imagenOrigen, $rutaCompletaDestino)) {
                $mensajeExito = "Reserva registrada exitosamente.";
            }
            return $response->withStatus(201)->withJson(['mensaje' => $mensajeExito]);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo crear la reserva']);
        }
    }

    public function listarReservas(Request $request, ResponseInterface $response)
    {
        try {
            $reservas = $this->reservaDAO->obtenerReservas();
            if ($reservas) {
                return $response->withStatus(200)->withJson($reservas);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontraron reservas']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
    public function consultarReserva(Request $request, ResponseInterface $response)
    {
        try {
            $parametros = $request->getQueryParams();
            $id = $parametros['id'] ?? null;

            if ($id == null) {
                return $response->withStatus(400)->withJson(['error' => 'Debe ingresar el ID de la reserva que desea consultar.']);
            }
            $reserva = $this->reservaDAO->obtenerReservaPorId($id);
            if ($reserva) {
                return $response->withStatus(200)->withJson($reserva);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontró la reserva.']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
}