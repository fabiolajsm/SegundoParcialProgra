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
}
?>