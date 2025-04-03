<?php 
session_start();

$connection = include('./conexion.php');

//Eror reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Habilitar excepciones en MySQLi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


// Guardar datos de formulario de producto seleccionado en sesión
if (isset($_POST['product_id'], $_POST['product_name'], $_POST['product_price'], $_POST['category'])) {
    // Inicializar el carrito si no existe
    if (!isset($_SESSION['compra'])) {
        $_SESSION['compra'] = [];
    }

    // Decodificar la lista de ingredientes de JSON a array
    $lista_ingredientes = isset($_POST['ingr_list_info']) ? json_decode($_POST['ingr_list_info'], true) : [];

    // Si la decodificación falla, aseguramos que sea un array vacío
    if (!is_array($lista_ingredientes)) {
        $lista_ingredientes = [];
    }

    // Obtener nombres de los ingredientes desde la base de datos
    $ingredientes_formateados = [];

    if (!empty($lista_ingredientes)) {
        foreach ($lista_ingredientes as $ingr) {
            $id = $ingr[0]; // ID del ingrediente
            $cantidad = $ingr[1]; // Nueva cantidad
            $estado = $ingr[2]; // Estado (Añadido/Eliminado)

            // Consultar el nombre del ingrediente
            $query = "SELECT ingredient_name FROM INGREDIENTS WHERE ingredient_id = ?";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $nombre_ingrediente = $resultado->fetch_assoc()['ingredient_name'] ?? "Desconocido";

            // Formatear el texto del ingrediente
            $texto_ingr = "{$nombre_ingrediente} ({$estado})";
            $ingredientes_formateados[] = $texto_ingr;
        }
    }

    // Agregar el producto al carrito con los ingredientes formateados
    $_SESSION['compra'][] = [
        'id' => $_POST['product_id'],
        'nombre' => $_POST['product_name'],
        'precio' => $_POST['product_price'],
        'cantidad' => 1,
        'lista_ingredientes' => $ingredientes_formateados, // Ahora contiene los nombres formateados
        'category' => $_POST['category']
    ];
}

header('Location: ./menu.php');
?>
