<?php
class ManejadorArchivos
{
    public function subirImagen($rutaImagen)
    {
        $directorio = dirname($rutaImagen);
        if (!file_exists($directorio)) {
            echo "no existe el directorio";
            // Si el directorio no existe, créalo
            if (!mkdir($directorio, 0777, true)) {
                echo "no pude crearlo";
                return false; // No se pudo crear el directorio
            }
        }
        if (isset($_FILES['fotoDelCliente']) && move_uploaded_file($_FILES['fotoDelCliente']['tmp_name'], $rutaImagen)) {
            return true;
        } else {
            return false;
        }
    }
}
?>