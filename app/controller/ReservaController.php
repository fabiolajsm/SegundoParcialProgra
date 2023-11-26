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

        $clienteExistente = $this->reservaDAO->obtenerClientePorIdyTipo($nroCliente, $tipoCliente);
        if (!$clienteExistente) {
            return $response->withStatus(400)->withJson(['error' => 'El cliente no ha sido encontrado.']);
        }
        $idReserva = $this->reservaDAO->crearReserva($nroCliente, $clienteExistente['tipo'], $fechaEntradaFormateada, $fechaSalidaFormateada, $tipoHabitacion, $importeTotal);
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
    /* a- El total de reservas (importe) por tipo de habitación y fecha en un día en particular
    (se envía por parámetro), si no se pasa fecha, se muestran las del día anterior.*/
    public function consultarReservasPorFecha(Request $request, ResponseInterface $response)
    {
        try {
            $parametros = $request->getQueryParams();
            $tiposHabitacion = array('SIMPLE', 'DOBLE', 'SUITE');
            $tipoHabitacion = $parametros['tipoHabitacion'] ?? null;
            $fechaConsulta = $parametros['fechaConsulta'] ?? null;

            if ($tipoHabitacion == null) {
                return $response->withStatus(400)->withJson(['error' => 'Tiene que ingresar un tipo de habitación']);
            }
            $tipoHabitacion = strtoupper($tipoHabitacion);
            if (!in_array($tipoHabitacion, $tiposHabitacion)) {
                return $response->withStatus(400)->withJson(['error' => 'Tipo de habitacion incorrecto. Debe ser de tipo: SIMPLE, DOBLE o SUITE.']);
            }

            $totalReservas = $this->reservaDAO->obtenerTotalReservasPorTipoYFecha($tipoHabitacion, $fechaConsulta);
            return $response->withStatus(200)->withJson(['totalReservas' => $totalReservas]);
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
    /* b- El listado de reservas para un cliente en particular.*/
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
    /* c- El listado de reservas entre dos fechas ordenado por fecha. */
    public function listarReservasEntreFechas(Request $request, ResponseInterface $response)
    {
        try {
            $parametros = $request->getQueryParams();
            $fechaInicio = $parametros['fechaInicio'] ?? null;
            $fechaFin = $parametros['fechaFin'] ?? null;

            if ($fechaInicio == null || $fechaFin == null) {
                return $response->withStatus(400)->withJson(['error' => 'Debe ingresar las fechas de inicio y fin para la consulta.']);
            }

            try {
                $fechaInicioObj = new DateTime($fechaInicio);
                $fechaFinObj = new DateTime($fechaFin);
            } catch (Exception $e) {
                return $response->withStatus(400)->withJson(['error' => 'Error: Las fechas proporcionadas no son válidas.']);
            }

            $fechaInicioFormateada = $fechaInicioObj->format('Y-m-d');
            $fechaFinFormateada = $fechaFinObj->format('Y-m-d');

            $reservas = $this->reservaDAO->obtenerReservasEntreFechas($fechaInicioFormateada, $fechaFinFormateada);
            if ($reservas) {
                // Ordenar las reservas por fecha
                usort($reservas, function ($a, $b) {
                    return strtotime($a['fechaEntrada']) - strtotime($b['fechaEntrada']);
                });
                return $response->withStatus(200)->withJson($reservas);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontraron reservas entre las fechas proporcionadas.']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
    /* d- El listado de reservas por tipo de habitación. */
    public function listarReservasPorTipoHabitacion(Request $request, ResponseInterface $response)
    {
        try {
            $parametros = $request->getQueryParams();
            $tiposHabitacion = array('SIMPLE', 'DOBLE', 'SUITE');
            $tipoHabitacion = $parametros['tipoHabitacion'] ?? null;

            if ($tipoHabitacion == null) {
                return $response->withStatus(400)->withJson(['error' => 'Tiene que ingresar un tipo de habitación']);
            }
            $tipoHabitacion = strtoupper($tipoHabitacion);
            if (!in_array($tipoHabitacion, $tiposHabitacion)) {
                return $response->withStatus(400)->withJson(['error' => 'Tipo de habitacion incorrecto. Debe ser de tipo: SIMPLE, DOBLE o SUITE.']);
            }

            $reservas = $this->reservaDAO->obtenerReservasPorTipoHabitacion($tipoHabitacion);
            if ($reservas) {
                return $response->withStatus(200)->withJson($reservas);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontraron reservas para el tipo de habitación proporcionado.']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
}