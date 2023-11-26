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
            $stmt = $this->pdo->prepare("SELECT * FROM clientes WHERE ID = ? AND tipo = ? AND activo = 1");
            $stmt->execute([$id, $tipo]);
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
            $stmt->execute([$nroCliente, strtoupper($tipoCliente), $fechaEntrada, $fechaSalida, strtoupper($tipoHabitacion), $importeTotal, 1, $horaActual]);
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
    public function consultarReserva($id)
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
}
?>