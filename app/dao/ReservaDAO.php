<?php

class ReservaDAO
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    public function obtenerClientePorIdyTipo($id, $tipo)
    {
        try {
            $tipoBusqueda = '%' . $tipo . '%';
            $stmt = $this->pdo->prepare("SELECT * FROM clientes WHERE ID = ? AND tipo LIKE ? AND activo = 1");
            $stmt->execute([$id, $tipoBusqueda]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            return $cliente;
        } catch (PDOException $e) {
            echo 'Error al obtener cliente: ' . $e->getMessage();
            return false;
        }
    }
    public function crearReserva($nroCliente, $tipoCliente, $fechaEntrada, $fechaSalida, $tipoHabitacion, $importeTotal)
    {
        try {
            $horaActual = date('Y-m-d H:i:s');
            $stmt = $this->pdo->prepare("INSERT INTO reservas (nroCliente, tipoCliente, fechaEntrada, fechaSalida, tipoHabitacion, importeTotal, activo, horaDeAlta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nroCliente, $tipoCliente, $fechaEntrada, $fechaSalida, strtoupper($tipoHabitacion), $importeTotal, 1, $horaActual]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            echo 'Error al agregar reserva: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerReservas()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM reservas");
            $stmt->execute();
            $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $reservas;
        } catch (PDOException $e) {
            echo 'Error al listar reservas: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerReservaPorId($id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM reservas WHERE ID = ? AND activo = 1");
            $stmt->execute([$id]);
            $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
            return $reserva;
        } catch (PDOException $e) {
            echo 'Error al obtener la reserva: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerTotalReservasPorTipoYFecha($tipoHabitacion, $fecha = null)
    {
        try {
            if ($fecha === null) {
                $fecha = date('Y-m-d', strtotime('-1 day'));
            }
            $stmt = $this->pdo->prepare("SELECT tipoHabitacion, SUM(importeTotal) as totalImporte FROM reservas WHERE fechaEntrada = ? GROUP BY tipoHabitacion");
            $stmt->execute([$fecha]);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $resultados;
        } catch (PDOException $e) {
            echo 'Error al obtener el total de reservas por tipo de habitación y fecha: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerReservasEntreFechas($fechaInicio, $fechaFin)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM reservas WHERE fechaEntrada >= ? AND fechaSalida <= ? AND activo = 1");
            $stmt->execute([$fechaInicio, $fechaFin]);
            $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $reservas;
        } catch (PDOException $e) {
            echo 'Error al obtener las reservas entre fechas: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerReservasPorTipoHabitacion($tipoHabitacion)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM reservas WHERE tipoHabitacion = ? AND activo = 1");
            $stmt->execute([strtoupper($tipoHabitacion)]);
            $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $reservas;
        } catch (PDOException $e) {
            echo 'Error al obtener las reservas por tipo de habitación: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerTotalCancelacionesPorTipoYFecha($tipoCliente, $fecha = null)
    {
        try {
            if ($fecha === null) {
                $fecha = date('Y-m-d', strtotime('-1 day'));
            }
            $tipoBusqueda = '%' . $tipoCliente . '%';
            $stmt = $this->pdo->prepare("SELECT tipoCliente, COUNT(*) as totalCancelaciones FROM reservas WHERE tipoCliente LIKE ? AND activo = 0 AND fechaCancelacion LIKE ? GROUP BY tipoCliente");
            $stmt->execute([$tipoBusqueda, $fecha . '%']);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $resultados;
        } catch (PDOException $e) {
            echo 'Error al obtener el total de cancelaciones por tipo de cliente y fecha: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerCancelacionesPorCliente($idCliente)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM reservas WHERE nroCliente = ? AND activo = 0");
            $stmt->execute([$idCliente]);
            $cancelaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $cancelaciones;
        } catch (PDOException $e) {
            echo 'Error al obtener las cancelaciones por cliente: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerCancelacionesEntreFechas($fechaInicio, $fechaFin)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM reservas WHERE activo = 0 AND fechaCancelacion BETWEEN ? AND ?");
            $stmt->execute([$fechaInicio, $fechaFin]);
            $cancelaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $cancelaciones;
        } catch (PDOException $e) {
            echo 'Error al obtener las cancelaciones entre fechas: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerCancelacionesPorTipoCliente($tipoCliente)
    {
        try {
            $tipoClienteBusqueda = '%' . strtoupper($tipoCliente) . '%';
            $stmt = $this->pdo->prepare("SELECT * FROM reservas WHERE activo = 0 AND tipoCliente LIKE ?");
            $stmt->execute([$tipoClienteBusqueda]);
            $cancelaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $cancelaciones;
        } catch (PDOException $e) {
            echo 'Error al obtener las cancelaciones por tipo de cliente: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerOperacionesPorCliente($nroCliente)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM reservas WHERE nroCliente = ?");
            $stmt->execute([$nroCliente]);
            $operaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $operaciones;
        } catch (PDOException $e) {
            echo 'Error al obtener las operaciones por nroCliente: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerReservasPorModalidad($modalidad)
    {
        try {
            // Buscar los clientes que tienen esa modalidad de pago
            $stmtClientes = $this->pdo->prepare("SELECT ID FROM clientes WHERE modalidadPago = ? AND activo = 1");
            $stmtClientes->execute([$modalidad]);
            $clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

            // Obtener las reservas de los clientes encontrados
            $nrosClientes = array_column($clientes, 'ID');
            $placeholders = rtrim(str_repeat('?, ', count($nrosClientes)), ', ');

            if (empty($nrosClientes)) {
                return []; // No hay clientes con esa modalidad de pago
            }
            $stmtReservas = $this->pdo->prepare("SELECT * FROM reservas WHERE nroCliente IN ($placeholders)");
            $stmtReservas->execute($nrosClientes);
            $reservas = $stmtReservas->fetchAll(PDO::FETCH_ASSOC);
            return $reservas;
        } catch (PDOException $e) {
            echo 'Error al obtener las reservas por modalidad: ' . $e->getMessage();
            return false;
        }
    }
    public function cancelarReserva($idReserva)
    {
        try {
            $fechaCancelacion = date('Y-m-d');
            $stmt = $this->pdo->prepare("UPDATE reservas SET activo = 0, fechaCancelacion = ? WHERE ID = ?");
            $stmt->execute([$fechaCancelacion, $idReserva]);
            return true;
        } catch (PDOException $e) {
            echo 'Error al cancelar la reserva: ' . $e->getMessage();
            return false;
        }
    }
}
?>