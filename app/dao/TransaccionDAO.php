<?php
class TransaccionesDAO
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function registrarTransaccion($usuario, $numeroOperacion)
    {
        try {
            $fecha = date('Y-m-d');
            $hora = date('H:i:s');
            $stmt = $this->pdo->prepare("INSERT INTO transacciones (usuario, fecha, hora, numeroOperacion) VALUES (?, ?, ?, ?)");
            $stmt->execute([$usuario, $fecha, $hora, $numeroOperacion]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            echo 'Error al insertar transacción: ' . $e->getMessage();
            return false;
        }
    }
}
?>