<?php

class ClienteDAO
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    public function existeCliente($nroDocumento)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM clientes WHERE nroDocumento = ? AND activo = 1");
            $stmt->execute([$nroDocumento]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            return $cliente;
        } catch (PDOException $e) {
            echo 'Error al obtener cliente: ' . $e->getMessage();
            return false;
        }
    }
    public function crearCliente($nombre, $apellido, $tipoDocumento, $nroDocumento, $tipo, $pais, $ciudad, $email, $telefono, $modalidadPago)
    {
        try {
            $horaActual = date('Y-m-d H:i:s');
            $idUnico = $this->generarIdUnico();
            $stmt = $this->pdo->prepare("INSERT INTO clientes (ID, nombre, apellido, tipoDocumento, nroDocumento, tipo, pais, ciudad, email, telefono, modalidadPago, activo, horaDeAlta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$idUnico, strtoupper($nombre), strtoupper($apellido), $tipoDocumento, $nroDocumento, strtoupper($tipo), strtoupper($pais), strtoupper($ciudad), strtoupper($email), $telefono, $modalidadPago, 1, $horaActual]);
            return $idUnico;
        } catch (PDOException $e) {
            echo 'Error al insertar cliente: ' . $e->getMessage();
            return false;
        }
    }
    private function generarIdUnico()
    {
        $codigo = str_pad(mt_rand(1, 99999), 6, '0', STR_PAD_LEFT);

        while ($this->codigoExisteEnBD($codigo)) {
            $codigo = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        }
        return $codigo;
    }
    private function codigoExisteEnBD($codigo)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM clientes WHERE ID = ? AND activo = 1");
        $stmt->execute([$codigo]);
        $count = $stmt->fetchColumn();
        return $count > 0;
    }
    public function obtenerClientes()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM clientes");
            $stmt->execute();
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $clientes;
        } catch (PDOException $e) {
            echo 'Error al listar clientes: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerClientePorId($id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM clientes WHERE ID = ? AND activo = 1");
            $stmt->execute([$id]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            return $cliente;
        } catch (PDOException $e) {
            echo 'Error al obtener cliente: ' . $e->getMessage();
            return false;
        }
    }
    public function darDeBajaCliente($id)
    {
        try {
            $horaDeBaja = date('Y-m-d H:i:s');
            $sql = "UPDATE clientes SET activo = 0, horaDeBaja = ? WHERE ID = ? AND activo = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$horaDeBaja, $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            echo 'Error al dar de baja el cliente: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerCliente($id, $tipoCliente)
    {
        try {
            $tipoCliente = strtoupper($tipoCliente);
            $sql = "SELECT * FROM clientes WHERE ID = ? AND tipo LIKE CONCAT('%', ?, '%') AND activo = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id, $tipoCliente]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            return $cliente;
        } catch (PDOException $e) {
            echo 'Error al obtener cliente: ' . $e->getMessage();
            return false;
        }
    }
}
?>