<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "judomex";

    header('Content-Type: application/json');

    try {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (empty($data['email']) || empty($data['contrasena'])) {
            throw new Exception('Email y contraseña son requeridos');
        }

        $conn = new mysqli($servername, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            throw new Exception("Error de conexión: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("SELECT id_usuario, nombre, password FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $data['email']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception('Credenciales incorrectas');
        }

        $user = $result->fetch_assoc();
        
        if (!password_verify($data['contrasena'], $user['password'])) {
            throw new Exception('Credenciales incorrectas');
        }

        // Establecer datos de sesión
        $_SESSION['usuario_id'] = $user['id_usuario'];
        $_SESSION['usuario_nombre'] = $user['nombre'];
        $_SESSION['email'] = $data['email'];

        session_write_close();

        echo json_encode([
            'success' => true,
            'message' => 'Login exitoso',
            'redirect' => 'Inicio.php'
        ]);
        exit();

    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <link rel="website icon" type="png" href="assets/logo.png">
    <link rel="stylesheet" href="InicioSesion.css" type="text/css"/>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <!-- Título JUDOMEX -->
    <section class="container_Judomex">
        <div class="texto_Judomex">JUDOMEX</div>
    </section>

    <!-- Incio de sesión -->
    <section class="container_InicioSesion">
        <!-- Mensaje de error (añadido) -->
        <div id="mensajeError" class="mensaje_error"></div>

        <!-- Ingresa el correo -->
        <section class="container_Usuario">
            <div class="texto_Seccion">Correo electrónico:</div>
            <div class="container_Campo">
                <input type="email" id="inputEmail" class="texto_Llenador" placeholder="Escribe tu correo electrónico" required>
            </div>
        </section>

        <!-- Ingresa la contraseña -->
        <section class="container_Contraseña">
            <div class="texto_Seccion">Contraseña:</div>
            <div class="container_Campo">
                <input type="password" id="inputContrasena" class="texto_Llenador" placeholder="Escribe tu contraseña" required>
            </div>
        </section>

        <a href="Registro.html">
            <div class="texto_NuevaCuenta"><u>Crea una nueva cuenta</u></div>
        </a>

        <!-- Acceso a google -->
         <a href="https://accounts.google.com/ServiceLogin?hl=es&service=mail">
            <div class="button_Google">
                <i class="fa-solid fa-g"></i>
            </div>
         </a>
         

         <!-- Botón de Iniciar Sesión -->
         <button id="btnIniciarSesion" class="button_Iniciar">
            <div class="texto_IniciarSesion">Iniciar Sesión</div>
        </button>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnIniciarSesion = document.getElementById('btnIniciarSesion');
            const inputEmail = document.getElementById('inputEmail');
            const inputContrasena = document.getElementById('inputContrasena');
            const mensajeError = document.getElementById('mensajeError');

            btnIniciarSesion.addEventListener('click', async function() {
                // Validar campos vacíos
                if (!inputEmail.value || !inputContrasena.value) {
                    mostrarError('Por favor completa todos los campos');
                    return;
                }

                // Mostrar estado de carga
                const originalText = btnIniciarSesion.innerHTML;
                btnIniciarSesion.disabled = true;
                btnIniciarSesion.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';

                try {
                    const response = await fetch('InicioSesion.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            email: inputEmail.value,
                            contrasena: inputContrasena.value
                        })
                    });

                    const data = await response.json();
                    console.log("Respuesta del servidor:", data);

                    if (!data.success) {
                        throw new Error(data.message);
                    }

                    // Redirigir después de login exitoso
                    window.location.href = `./${data.redirect}`;
                    
                } catch (error) {
                    console.error('Error:', error);
                    mostrarError(error.message);
                } finally {
                    btnIniciarSesion.disabled = false;
                    btnIniciarSesion.innerHTML = originalText;
                }
            });
            
            function mostrarError(mensaje) {
                mensajeError.textContent = mensaje;
                mensajeError.style.display = 'block';
                
                // Ocultar el mensaje después de 3 segundos
                setTimeout(() => {
                    mensajeError.style.display = 'none';
                }, 3000);
            }
            
            // Permitir iniciar sesión presionando Enter
            inputContrasena.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    btnIniciarSesion.click();
                }
            });
        });
    </script>
</body>
</html>