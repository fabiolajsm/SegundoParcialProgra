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
    public function obtenerAjustes(Request $request, ResponseInterface $response)
    {
        try {
            $ajustes = $this->reservaDAO->obtenerAjustes();
            if ($ajustes) {
                return $response->withStatus(200)->withJson($ajustes);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontraron ajustes']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
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
    /* a.2 - El total cancelado (importe) por tipo de cliente y fecha en un día en particular 
    (se envía por parámetro), si no se pasa fecha, se muestran las del día anterior. */
    public function obtenerTotalCancelacionesPorTipoYFecha(Request $request, ResponseInterface $response)
    {
        try {
            $parametros = $request->getQueryParams();
            $tiposCliente = array('INDI', 'CORPO');
            $tipoCliente = $parametros['tipoCliente'] ?? null;
            $fechaConsulta = $parametros['fechaConsulta'] ?? null;

            if ($tipoCliente == null) {
                return $response->withStatus(400)->withJson(['error' => 'Tiene que ingresar un tipo de cliente']);
            }
            $tipoCliente = strtoupper($tipoCliente);
            if (!in_array($tipoCliente, $tiposCliente)) {
                return $response->withStatus(400)->withJson(['error' => 'Tipo de cliente incorrecto. Debe ser de tipo: INDI o CORPO.']);
            }

            $totalCancelaciones = $this->reservaDAO->obtenerTotalCancelacionesPorTipoYFecha($tipoCliente, $fechaConsulta);
            return $response->withStatus(200)->withJson(['totalCancelaciones' => $totalCancelaciones]);
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
    /* b.2- El listado de cancelaciones para un cliente en particular.*/
    public function listarCancelacionesPorCliente(Request $request, ResponseInterface $response)
    {
        try {
            $parametros = $request->getQueryParams();
            $idCliente = $parametros['idCliente'] ?? null;

            if ($idCliente == null) {
                return $response->withStatus(400)->withJson(['error' => 'Debe ingresar el ID del cliente para consultar las cancelaciones.']);
            }
            $cancelaciones = $this->reservaDAO->obtenerCancelacionesPorCliente($idCliente);
            if ($cancelaciones) {
                return $response->withStatus(200)->withJson($cancelaciones);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontraron cancelaciones para el cliente proporcionado.']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
    /* c.2- El listado de cancelaciones entre dos fechas ordenado por fecha.*/
    public function listarCancelacionesEntreFechas(Request $request, ResponseInterface $response)
    {
        try {
            $parametros = $request->getQueryParams();
            $fechaInicio = $parametros['fechaInicio'] ?? null;
            $fechaFin = $parametros['fechaFin'] ?? null;

            if ($fechaInicio == null || $fechaFin == null) {
                return $response->withStatus(400)->withJson(['error' => 'Debe ingresar las fechas de inicio y fin para la consulta de cancelaciones.']);
            }

            try {
                $fechaInicioObj = new DateTime($fechaInicio);
                $fechaFinObj = new DateTime($fechaFin);
            } catch (Exception $e) {
                return $response->withStatus(400)->withJson(['error' => 'Error: Las fechas proporcionadas no son válidas.']);
            }

            $fechaInicioFormateada = $fechaInicioObj->format('Y-m-d');
            $fechaFinFormateada = $fechaFinObj->format('Y-m-d');

            $cancelaciones = $this->reservaDAO->obtenerCancelacionesEntreFechas($fechaInicioFormateada, $fechaFinFormateada);
            if ($cancelaciones) {
                // Ordenar las cancelaciones por fecha
                usort($cancelaciones, function ($a, $b) {
                    return strtotime($a['fechaCancelacion']) - strtotime($b['fechaCancelacion']);
                });
                return $response->withStatus(200)->withJson($cancelaciones);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontraron cancelaciones entre las fechas proporcionadas.']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }

    /* d.2- El listado de cancelaciones por tipo de cliente.*/
    public function listarCancelacionesPorTipoCliente(Request $request, ResponseInterface $response)
    {
        try {
            $parametros = $request->getQueryParams();
            $tiposCliente = array('INDI', 'CORPO');
            $tipoCliente = $parametros['tipoCliente'] ?? null;

            if ($tipoCliente == null) {
                return $response->withStatus(400)->withJson(['error' => 'Tiene que ingresar un tipo de cliente']);
            }
            $tipoCliente = strtoupper($tipoCliente);
            if (!in_array($tipoCliente, $tiposCliente)) {
                return $response->withStatus(400)->withJson(['error' => 'Tipo de cliente incorrecto. Debe ser de tipo: INDI o CORPO.']);
            }

            $cancelaciones = $this->reservaDAO->obtenerCancelacionesPorTipoCliente($tipoCliente);
            if ($cancelaciones) {
                return $response->withStatus(200)->withJson($cancelaciones);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontraron cancelaciones para el tipo de cliente proporcionado.']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
    /* e - El listado de todas las operaciones (reservas y cancelaciones) por usuario.*/
    public function listarOperacionesPorCliente(Request $request, ResponseInterface $response)
    {
        try {
            $parametros = $request->getQueryParams();
            $nroCliente = $parametros['nroCliente'] ?? null;

            if ($nroCliente == null) {
                return $response->withStatus(400)->withJson(['error' => 'Tiene que ingresar un nroCliente']);
            }

            $operaciones = $this->reservaDAO->obtenerOperacionesPorCliente($nroCliente);
            if ($operaciones) {
                return $response->withStatus(200)->withJson($operaciones);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontraron operaciones para el nroCliente proporcionado.']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
    /* f - El listado de Reservas por tipo de modalidad. */
    public function listarReservasPorModalidad(Request $request, ResponseInterface $response)
    {
        try {
            $parametros = $request->getQueryParams();
            $modalidad = $parametros['modalidad'] ?? null;

            if ($modalidad == null) {
                return $response->withStatus(400)->withJson(['error' => 'Tiene que ingresar una modalidad']);
            }

            $reservas = $this->reservaDAO->obtenerReservasPorModalidad($modalidad);
            if ($reservas) {
                return $response->withStatus(200)->withJson($reservas);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontraron reservas para la modalidad proporcionada.']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
    public function cancelarReserva(Request $request, ResponseInterface $response)
    {
        try {
            $data = $request->getParsedBody();
            $tipoCliente = $data['tipoCliente'] ?? null;
            $nroCliente = $data['nroCliente'] ?? null;
            $idReserva = $data['idReserva'] ?? null;
            $tiposCliente = array('INDI', 'CORPO');

            if ($tipoCliente == null || $nroCliente == null || $idReserva == null) {
                return $response->withStatus(400)->withJson(['error' => 'Debe ingresar tipoCliente, nroCliente y idReserva.']);
            }

            $tipoCliente = strtoupper($tipoCliente);
            if (!in_array($tipoCliente, $tiposCliente)) {
                return $response->withStatus(400)->withJson(['error' => 'Tipo de cliente incorrecto. Debe ser de tipo: INDI o CORPO.']);
            }

            $clienteExistente = $this->reservaDAO->obtenerClientePorIdyTipo($nroCliente, $tipoCliente);
            if (!$clienteExistente) {
                return $response->withStatus(400)->withJson(['error' => 'El cliente no ha sido encontrado.']);
            }

            $reserva = $this->reservaDAO->obtenerReservaPorId($idReserva);
            if (!$reserva) {
                return $response->withStatus(404)->withJson(['error' => 'La reserva no ha sido encontrada.']);
            }

            $cancelada = $this->reservaDAO->cancelarReserva($idReserva);
            if ($cancelada) {
                return $response->withStatus(200)->withJson(['mensaje' => 'Reserva cancelada exitosamente.']);
            } else {
                return $response->withStatus(500)->withJson(['error' => 'No se pudo cancelar la reserva.']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
    public function ajustarReserva(Request $request, ResponseInterface $response)
    {
        try {
            $data = $request->getParsedBody();
            $idReserva = $data['idReserva'] ?? null;
            $ajuste = $data['ajuste'] ?? null;
            $motivo = $data['motivo'] ?? null;

            if ($idReserva == null || $ajuste == null || $motivo == null) {
                return $response->withStatus(400)->withJson(['error' => 'Debe ingresar idReserva, ajuste y motivo.']);
            }
            $reserva = $this->reservaDAO->obtenerReservaPorId($idReserva);
            if (!$reserva) {
                return $response->withStatus(404)->withJson(['error' => 'La reserva no ha sido encontrada.']);
            }
            $ajusteRealizado = $this->reservaDAO->ajustarReserva($idReserva, $ajuste, $motivo);
            if ($ajusteRealizado) {
                return $response->withStatus(200)->withJson(['mensaje' => 'Reserva ajustada exitosamente.']);
            } else {
                return $response->withStatus(500)->withJson(['error' => 'No se pudo ajustar la reserva.']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
}