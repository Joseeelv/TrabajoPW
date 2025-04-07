## Configuración y Ficheros Generales

Este apartado describe los ficheros clave utilizados para la configuración y la estructura general del sistema web de Döner Kebab Society.

### `.configDB.php`

Este fichero contiene las constantes de configuración necesarias para establecer la conexión con la base de datos. Define los siguientes parámetros:

- `DB_SERVER`: Dirección del servidor de base de datos (por defecto, `localhost`).
- `DB_USERNAME`: Nombre de usuario para la base de datos (`root`).
- `DB_PASSWORD`: Contraseña para el acceso (vacía por defecto).
- `DB_NAME`: Nombre de la base de datos (`DB_KEBAB`).

**Ejemplo de código:**
```php
// .configDB.php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'DB_KEBAB');
```

### `conexion.php`

Fichero que utiliza `.configDB.php` para crear y devolver una conexión activa a la base de datos. Si la conexión falla, se interrumpe el proceso y se muestra un mensaje de error.

**Ejemplo de código:**
```php
require_once '.configDB.php';
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
```

### `footer.php`

Contiene el pie de página común del sitio web, con la siguiente información:

- Información legal.
- Dirección de la tienda.
- Copyright con el año actual.
- Enlaces a redes sociales: Facebook, Twitter e Instagram.

### `header.php` y `navbar.php`

Define la barra de navegación del sitio web. Los elementos del menú se personalizan en función del tipo de usuario:

- **Admin**: Acceso a panel de administración, empleados, contratación, perfil y transacciones.
- **Manager**: Acceso a panel de manager, reabastecimiento, transacciones y perfil.
- **Customer**: Acceso a dashboard, ofertas, menú, carrito, pedidos recientes y perfil.
- **Visitante**: Opciones públicas como inicio, carta, contacto, login y registro.

También incluye elementos visuales como:

- Imagen del logo ("Kebabito") para clientes.
- Imagen de perfil personalizada (o por defecto).

**Ejemplo de lógica condicional:**
```php
if ($_SESSION['role'] === 'admin') {
    echo '<a href="admin.php">Panel Admin</a>';
} elseif ($_SESSION['role'] === 'manager') {
    echo '<a href="manager_index.php">Panel Manager</a>';
}
```

### `index.php`

La página principal del sitio web, accesible públicamente. Incluye:

- Cabecera con navbar.
- Carrusel de imágenes promocionales.
- Botón de registro destacado.
- Inclusión del pie de página (`footer.php`).

### `admin.php`

Panel de control para usuarios administradores. Incluye:

- Bienvenida personalizada.
- Imagen de perfil.
- Botones para gestionar empleados, contratar, revisar transacciones y ver el perfil.

### `manager_index.php`

Panel principal para los managers. Funcionalidades similares al panel de admin, pero centradas en tareas de gestión como:

- Reabastecimiento de productos.
- Consulta de transacciones.
- Acceso al perfil.

### `dashboard.php` (Customer)

Panel principal para clientes registrados. Funciones destacadas:

- Muestra puntos actuales del cliente desde la base de datos.
- Carrusel de imágenes promocionales.

**Ejemplo de obtención de puntos:**
```php
$user_id = $_SESSION['id'];
$query = "SELECT puntos FROM CUSTOMERS WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($puntos);
$stmt->fetch();
echo "Tienes $puntos puntos.";
```

---

## Módulo de Manager

Este módulo permite a los usuarios con rol de manager gestionar el reabastecimiento de ingredientes y productos, así como visualizar las transacciones económicas resultantes.

### `manager_replineshment.php`

Interfaz principal de reabastecimiento. Incluye:

- Listado de ingredientes con su imagen, nombre, precio unitario, stock actual y formulario para pedir más cantidad.
- Listado de productos tipo bebida o postre con su respectiva información y formulario de pedido.
- Lógica para mostrar mensajes de éxito tras una reposición.
- Consulta a la base de datos para obtener información actualizada de `INGREDIENTS` y `PRODUCTS`.
- Los formularios envían los datos a `replenishment.php`.

**Ejemplo de formulario para reabastecer:**
```php
<form method="post" action="replenishment.php">
  <input type="hidden" name="id" value="<?= $row['id'] ?>">
  <input type="number" name="cantidad" min="1">
  <button type="submit">Reabastecer</button>
</form>
```

### `replenishment.php`

Procesa las solicitudes de reabastecimiento. Funcionalidades:

- Inserta una nueva entrada en la tabla `REPLENISHMENTS`.
- Inserta automáticamente una transacción económica en `TRANSACTIONS`.
- Actualiza el stock del producto o ingrediente.
- Redirige con un mensaje de éxito.

**Fragmento de lógica principal:**
```php
$conn->begin_transaction();
$conn->query("INSERT INTO REPLENISHMENTS (...) VALUES (...)");
$conn->query("UPDATE INGREDIENTS SET stock = stock + $cantidad WHERE id = $id");
$conn->query("INSERT INTO TRANSACTIONS (...) VALUES (...)");
$conn->commit();
```

### `transactions.php`

Muestra un historial de todas las transacciones. Funciones:

- Consulta unificada de transacciones.
- Cálculo de totales y balance.
- Coloreado condicional por tipo.

**Ejemplo de consulta:**
```php
SELECT fecha, tipo, cantidad, total FROM TRANSACTIONS ORDER BY fecha DESC
```

---

## Módulo de Admin

El módulo de administrador está diseñado para gestionar los empleados del sistema, específicamente los managers. Permite contratarlos, despedirlos y recontratarlos.

### `employees.php`

Página principal para la gestión de empleados. Funcionalidades:

- Muestra managers contratados (`employee = 1`).
- Tabla con información personal y opción para despedir.
- Formulario de selección múltiple.

**Fragmento de tabla:**
```php
<form action="despedir.php" method="post">
<?php while($row = $result->fetch_assoc()): ?>
<tr>
  <td><input type="checkbox" name="ids[]" value="<?= $row['id'] ?>"></td>
  <td><?= $row['nombre'] ?></td>
</tr>
<?php endwhile; ?>
<button type="submit">Despedir</button>
</form>
```

### `despedir.php`

Script que procesa el despido. Funciones clave:

- Valida selección de managers.
- Usa transacción para marcar `employee = 0`.
- Muestra mensajes y redirige.

**Lógica central:**
```php
$conn->begin_transaction();
foreach ($_POST['ids'] as $id) {
  $conn->query("UPDATE MANAGERS SET employee = 0 WHERE id = $id");
}
$conn->commit();
```

### `contratar.php`

Archivo para registrar nuevos managers. Funcionalidades:

- Validación mediante clase `Validator`.
- Inserción en `USERS` y `MANAGERS`.
- Tabla de activos y despedidos.
- Botón para recontratar despedidos.

**Inserción con validación:**
```php
if ($validator->validate($username, $password)) {
  $conn->begin_transaction();
  // Insert en USERS y MANAGERS
  $conn->commit();
}
```

**Ejemplo de clase Validator:**
```php
class Validator {
  public function validate($username, $password) {
    return strlen($username) >= 4 && strlen($password) >= 6;
  }
}
```

### `recontratar.php`

Script para reactivar managers despedidos. Funciones:

- Transacción para cambiar `employee` a `1`.
- Redirección y mensajes.

**Código de actualización:**
```php
$conn->begin_transaction();
foreach ($_POST['ids'] as $id) {
  $conn->query("UPDATE MANAGERS SET employee = 1 WHERE id = $id");
}
$conn->commit();
```
