<?php
session_start();
include("db/conexion.php");

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['cantidad'])) {
    $id = intval($_POST['id']);
    $cantidad = intval($_POST['cantidad']);
    if ($cantidad > 0) {
        $encontrado = false;
        foreach ($_SESSION['carrito'] as &$producto) {
            if ($producto['id'] === $id) {
                $producto['cantidad'] += $cantidad;
                $encontrado = true;
                break;
            }
        }
        if (!$encontrado) {
            $_SESSION['carrito'][] = ['id' => $id, 'cantidad' => $cantidad];
        }
    }
    // Redirigir para evitar reenvío al refrescar
    header("Location: tienda.php");
    exit();
}

// Contador de productos en carrito
$contador = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $producto) {
        $contador += $producto['cantidad'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Madre Agua ST | Tienda de Construcción</title>
  <link rel="stylesheet" href="styles/tienda.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="styles/aos.css" />
  <script src="scripts/aos.js"></script>
  <script>AOS.init();</script>
  
  <!-- Agregar jQuery para el slider (si no está ya incluido) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css">
  
  <style>
    /* Estilos para las nuevas tarjetas animadas */
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700;800;900&display=swap');
    
    .slide-container {
        margin: auto;
        width: 90%;
        text-align: center;
    }
    
    .wrapper {
        padding-top: 40px;
        padding-bottom: 40px;
    }
    
    .wrapper:focus {
        outline: 0;
    }
    
    .card {
        position: relative;
        display: inline-block;
        width: 320px;
        height: 420px;
        background: #122936;
        border-radius: 20px;
        overflow: hidden;
        z-index: 9999;
        margin: 15px;
    }
    
    .card::before {
        content: '';
        position: absolute;
        top: -50%;
        left: 0;
        width: 100%;
        height: 100%;
        background: #cee945;
        transform: skewY(345deg);
        transition: 0.5s;
    }
    
    .card:hover::before {
        top: -70%;
        transform: skewY(390deg);
    }
    
    .card::after {
        content: 'Madre Agua';
        position: absolute;
        bottom: 0;
        left: 0;
        font-weight: 600;
        font-size: 8em;
        color: rgba(0, 0, 0, 0.1);
        transition: 0.5s;
    }
    
    .card:hover::after {
        left: -100px;
    }
    
    .card .image-background {
        position: relative;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        padding-top: 20px;
        z-index: 1;
    }
    
    .card .image-background img {
        max-width: 100%;
        transition: 0.5s;
    }
    
    .card:hover .image-background img {
        max-width: 80%;
    }
    
    .card .content-background {
        position: relative;
        padding: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        z-index: 1;
    }
    
    .card .content-background h3 {
        font-size: 18px;
        color: #fff;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .card .content-background .price {
        font-size: 24px;
        color: #fff;
        font-weight: 500;
        letter-spacing: 1px;
    }
    
    .card .content-background .buy {
        position: relative;
        top: 200px;
        opacity: 0;
        padding: 10px 30px;
        margin-top: 15px;
        color: #fff;
        text-decoration: none;
        background: #cee945;
        border-radius: 30px;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: 0.5s;
        border: none;
        cursor: pointer;
    }
    
    .card:hover .content-background .buy {
        top: 0;
        opacity: 1;
    }
    
    .slick-prev {
        left: 100px;
        z-index: 999;
    }
    
    .slick-next {
        right: 100px;
        z-index: 999;
    }
    
    /* Estilos para el grid de productos existente */
    .grid-productos {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 30px;
        padding: 20px;
    }
  </style>
</head>
<body>
<div id="loader">
  <div class="spinner"></div>
</div>

  <header class="navbar">
    <div class="navbar-content">
      <div class="logo">
        <img src="img/logo.jpg" alt="Madre Agua ST">
        <h1>Madre Agua ST</h1>
      </div>

     <nav class="menu">
    <ul>
        <li><a href="index.php">Inicio</a></li>
        <li><a href="#categorias">Categorías</a></li>
        <li><a href="#productos">Productos</a></li>
        <li><a href="#contacto">Contacto</a></li>
        
    </ul>
</nav>

      <div class="carrito" onclick="toggleResumenCarrito()">
        🛒 <span id="contador-carrito">(<?php echo $contador; ?>)</span>
      </div>
    

  </header>

  <!-- Panel flotante del resumen del carrito -->
  <div id="resumen-carrito" class="resumen-carrito oculto">
    <h3>Resumen del carrito</h3>
    <ul id="lista-carrito">
        <!-- Los productos se cargarán aquí mediante JavaScript -->
    </ul>
    <p id="total-carrito">Total: $0 CUP</p>
    <a href="carrito.php" class="boton" onclick="irAlCarrito()">Ir al carrito</a>
  </div>

  <section id="inicio" class="hero">
    <div class="hero-content">
      <h2>Materiales de construcción con impacto social</h2>
      <p>Compra directo desde nuestra tienda y apoya proyectos locales en Cuba.</p>
      <a href="#productos" class="boton">Explorar productos</a>
    </div>
  </section>

  <section id="categorias" class="categorias">
    <h2>Categorías</h2>
    <div class="grid-categorias">
      <div class="categoria"><span>🧱</span> Cemento</div>
      <div class="categoria"><span>🧱</span> Bloques</div>
      <div class="categoria"><span>🔩</span> Acero</div>
      <div class="categoria"><span>🎨</span> Pintura</div>
      <div class="categoria"><span>🛡️</span> Seguridad</div>
    </div>
  </section>

  <!-- Nueva sección con carrusel de productos destacados -->
  <section id="destacados" class="destacados">
    <h2>Productos Destacados</h2>
    <div class="slide-container">
        <div class="wrapper">
            <?php
            $result_destacados = $conn->query("SELECT * FROM productos WHERE destacado = 1 LIMIT 5");
            if ($result_destacados && $result_destacados->num_rows > 0):
              while($row = $result_destacados->fetch_assoc()):
            ?>
            <div class="card">
                <div class="image-background">
                    <img src="img/productos/<?php echo $row['imagen']; ?>" alt="<?php echo $row['nombre']; ?>">
                </div>
                <div class="content-background">
                    <h3><?php echo $row['nombre']; ?></h3>
                    <p class="price">$<?php echo $row['precio']; ?> CUP</p>
                    <form method="post" action="tienda.php" class="form-card">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <input type="number" name="cantidad" value="1" min="1" class="cantidad-input">
                        <button type="submit" class="buy">Añadir al carrito</button>
                    </form>
                </div>
            </div>
            <?php
              endwhile;
            else:
              echo "<p>No hay productos destacados en este momento.</p>";
            endif;
            ?>
        </div>
    </div>
  </section>

  <!-- Sección de productos existente -->
  <section id="productos" class="grid-productos">
    <?php
    $result = $conn->query("SELECT * FROM productos");
    if ($result && $result->num_rows > 0):
      while($row = $result->fetch_assoc()):
    ?>
      <div class="card">
        <div class="image-background">
          <img src="img/productos/<?php echo $row['imagen']; ?>" alt="<?php echo $row['nombre']; ?>">
        </div>
        <div class="content-background">
          <h3><?php echo $row['nombre']; ?></h3>
          <p class="price">$<?php echo $row['precio']; ?> CUP</p>
          <form method="post" action="tienda.php" class="form-card">
            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
            <input type="number" name="cantidad" value="1" min="1" class="cantidad-input">
            <button type="submit" class="buy">Añadir al carrito</button>
          </form>
        </div>
      </div>
    <?php
      endwhile;
    else:
      echo "<p>No hay productos disponibles en este momento.</p>";
    endif;
    ?>
  </section>

  <section id="contacto" class="contacto">
    <h2>Contacto</h2>
    <p>📍 La Habana, Cuba</p>
    <p>📞 +53 7 1234567</p>
    <p>📧 contacto@madreaguast.cu</p>
  </section>

  <!-- Asistente Virtual Perrito -->
  <div class="asistente-container">
    <div class="chat-container" id="chatContainer">
      <div class="chat-header">
        <img src="img/agua.png" alt="Perrito Asistente">
        <span><strong>Perrito Ayudante</strong></span>
        <button class="cerrar-chat" id="cerrarChat">×</button>
      </div>
      <div class="chat-messages" id="chatMessages">
        <!-- Los mensajes se insertarán aquí dinámicamente -->
      </div>
      <div class="chat-input" id="chatInput">
        <!-- Las opciones se insertarán aquí dinámicamente -->
      </div>
    </div>
    
    <div class="asistente-boton" id="asistenteBoton">
      <i class="fas fa-dog"></i>
      <div class="notificacion" id="notificacion" style="display: none;">!</div>
    </div>
  </div>

  <footer>
    <p>&copy; 2025 Madre Agua ST. Todos los derechos reservados.</p>
  </footer>

  <!-- Toast para notificaciones -->
  <div id="toast" class="toast">Producto añadido al carrito ✅</div>

  <!-- Scripts -->
  <script>
    // Variables globales para el carrito
    let carrito = JSON.parse(localStorage.getItem("carrito")) || [];

    // Función para sincronizar carrito con el servidor
    function sincronizarCarritoConServidor() {
        const carrito = JSON.parse(localStorage.getItem("carrito")) || [];
        
        if (carrito.length > 0) {
            fetch('sincronizar_carrito.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ carrito: carrito })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Carrito sincronizado con el servidor');
            })
            .catch(error => {
                console.error('Error sincronizando carrito:', error);
            });
        }
    }

    // Llamar a esta función antes de redirigir al carrito
    function irAlCarrito() {
        sincronizarCarritoConServidor();
        // Redirigir después de un breve retraso para permitir la sincronización
        setTimeout(() => {
            window.location.href = 'carrito.php';
        }, 500);
    }

    // Función para agregar producto al carrito
    function agregarAlCarrito(id, nombre, precio, cantidad, imagen) {
      const existente = carrito.find(p => p.id === id);
      if (existente) {
        existente.cantidad += cantidad;
      } else {
        carrito.push({ id, nombre, precio, cantidad, imagen });
      }
      guardarCarrito();
      actualizarCarritoUI();
      mostrarToast();
    }

    // Función para eliminar producto del carrito
    function eliminarDelCarrito(id) {
      carrito = carrito.filter(p => p.id !== id);
      guardarCarrito();
      actualizarCarritoUI();
    }

    // Función para guardar carrito en localStorage
    function guardarCarrito() {
      localStorage.setItem("carrito", JSON.stringify(carrito));
    }

    // Función para actualizar la interfaz del carrito
    function actualizarCarritoUI() {
      document.getElementById("contador-carrito").textContent = `(${carrito.reduce((total, p) => total + p.cantidad, 0)})`;
      const lista = document.getElementById("lista-carrito");
      lista.innerHTML = "";
      let total = 0;
      carrito.forEach(p => {
        total += p.precio * p.cantidad;
        const item = document.createElement("li");
        item.innerHTML = `
          <img src="${p.imagen}" alt="${p.nombre}" class="producto-imagen-mini">
          <div class="producto-info-mini">
            <div class="producto-nombre-mini">${p.nombre}</div>
            <div class="producto-detalles-mini">
              <span>x${p.cantidad}</span>
              <span>$${(p.precio * p.cantidad).toFixed(2)} CUP</span>
            </div>
          </div>
          <button onclick="eliminarDelCarrito(${p.id})">✖</button>
        `;
        lista.appendChild(item);
      });
      document.getElementById("total-carrito").textContent = `Total: $${total.toFixed(2)} CUP`;
    }

    // Función para mostrar/ocultar resumen del carrito
    function toggleResumenCarrito() {
        const resumen = document.getElementById("resumen-carrito");
        resumen.classList.toggle("oculto");
        
        // Si estamos mostrando el carrito, actualizamos la UI
        if (!resumen.classList.contains("oculto")) {
            actualizarCarritoUI();
        }
    }

    // Función para animar producto al carrito
    function animarProductoAlCarrito(imgElement) {
      const carritoIcon = document.querySelector(".carrito");
      const imgRect = imgElement.getBoundingClientRect();
      const carritoRect = carritoIcon.getBoundingClientRect();
      
      // Efecto flash en el icono del carrito
      carritoIcon.classList.add("flash");
      setTimeout(() => carritoIcon.classList.remove("flash"), 400);

      // Clonar imagen para animación
      const flyImg = imgElement.cloneNode(true);
      flyImg.classList.add("fly-img");
      document.body.appendChild(flyImg);

      flyImg.style.left = imgRect.left + "px";
      flyImg.style.top = imgRect.top + "px";
      flyImg.style.width = imgRect.width + "px";
      flyImg.style.height = imgRect.height + "px";

      requestAnimationFrame(() => {
        flyImg.style.transform = `translate(${carritoRect.left - imgRect.left}px, ${carritoRect.top - imgRect.top}px) scale(0.2)`;
        flyImg.style.opacity = "0";
      });

      setTimeout(() => {
        flyImg.remove();
      }, 800);
    }

    // Función para mostrar toast de notificación
    function mostrarToast() {
      const toast = document.getElementById("toast");
      toast.classList.add("visible");
      setTimeout(() => toast.classList.remove("visible"), 2000);
    }

    // Configurar eventos para formularios de productos
    document.addEventListener('DOMContentLoaded', function() {
      // Configurar el slider para productos destacados
      $('.slide-container').slick({
        dots: true,
        infinite: true,
        speed: 300,
        slidesToShow: 3,
        slidesToScroll: 1,
        responsive: [
          {
            breakpoint: 1024,
            settings: {
              slidesToShow: 2,
              slidesToScroll: 1,
              infinite: true,
              dots: true
            }
          },
          {
            breakpoint: 600,
            settings: {
              slidesToShow: 1,
              slidesToScroll: 1
            }
          }
        ]
      });

      // Configurar eventos para formularios de productos
      document.querySelectorAll(".form-card").forEach(form => {
        form.addEventListener("submit", function(e) {
          e.preventDefault();

          const id = parseInt(this.querySelector("input[name='id']").value);
          const cantidad = parseInt(this.querySelector(".cantidad-input").value);
          const nombre = this.closest(".card").querySelector("h3").textContent;
          const precio = parseFloat(this.closest(".card").querySelector(".price").textContent.replace(/[^\d.]/g, ""));
          const img = this.closest(".card").querySelector("img");
          const imagen = img.src;

          animarProductoAlCarrito(img);
          agregarAlCarrito(id, nombre, precio, cantidad, imagen);
        });
      });

      // Inicializar carrito
      actualizarCarritoUI();

      // Modo oscuro automático
      const hora = new Date().getHours();
      if (hora >= 19 || hora <= 6) {
        document.body.classList.add("modo-oscuro");
      }

      // Ocultar loader cuando la página cargue
      window.addEventListener("load", () => {
        document.getElementById("loader").style.display = "none";
      });
    });
  </script>

  <!-- Script del Asistente Virtual -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const asistenteBoton = document.getElementById('asistenteBoton');
      const chatContainer = document.getElementById('chatContainer');
      const chatMessages = document.getElementById('chatMessages');
      const chatInput = document.getElementById('chatInput');
      const cerrarChat = document.getElementById('cerrarChat');
      const notificacion = document.getElementById('notificacion');

      let primeraVez = true;
      let sintesisVoz = null;

      // Inicializar síntesis de voz (para otros mensajes que no tengan audio)
      function inicializarVoz() {
        if ('speechSynthesis' in window) {
          sintesisVoz = window.speechSynthesis;
        }
      }

      // Función para reproducir audio pregrabado
      function reproducirAudio(audioFile) {
        const audio = new Audio(audioFile);
        
        // Añadir efecto visual de habla
        asistenteBoton.classList.add('hablando');
        
        audio.play().catch(error => {
          console.log('Error reproduciendo audio:', error);
          asistenteBoton.classList.remove('hablando');
        });
        
        audio.onended = function() {
          asistenteBoton.classList.remove('hablando');
        };
      }

      // Función para hablar (síntesis de voz para otros mensajes)
      function hablar(texto) {
        if (sintesisVoz) {
          // Cancelar cualquier speech en curso
          sintesisVoz.cancel();
          
          const utterance = new SpeechSynthesisUtterance(texto);
          utterance.lang = 'es-ES';
          utterance.rate = 0.9;
          utterance.pitch = 0.8;
          utterance.volume = 1.0;
          
          // Añadir efecto visual de habla
          asistenteBoton.classList.add('hablando');
          utterance.onend = function() {
            asistenteBoton.classList.remove('hablando');
          };
          
          utterance.onerror = function(event) {
            console.log('Error en síntesis de voz:', event);
            asistenteBoton.classList.remove('hablando');
          };
          
          sintesisVoz.speak(utterance);
        }
      }

      // Función para agregar mensaje al chat
      function agregarMensaje(texto, esUsuario = false, usarAudio = false, audioFile = null) {
        const mensajeDiv = document.createElement('div');
        mensajeDiv.className = `mensaje ${esUsuario ? 'mensaje-usuario' : 'mensaje-asistente'}`;
        mensajeDiv.textContent = texto;
        chatMessages.appendChild(mensajeDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        // Si es mensaje del asistente, reproducir audio o usar síntesis de voz
        if (!esUsuario) {
          setTimeout(() => {
            if (usarAudio && audioFile) {
              reproducirAudio(audioFile);
            } else {
              hablar(texto);
            }
          }, 500);
        }
      }

      // Función para mostrar opciones
      function mostrarOpciones(opciones) {
        chatInput.innerHTML = '';
        const opcionesDiv = document.createElement('div');
        opcionesDiv.className = 'opciones';
        
        opciones.forEach(opcion => {
          const boton = document.createElement('button');
          boton.className = 'opcion-btn';
          boton.textContent = opcion.texto;
          boton.onclick = () => {
            agregarMensaje(opcion.texto, true);
            opcion.accion();
          };
          opcionesDiv.appendChild(boton);
        });
        
        chatInput.appendChild(opcionesDiv);
      }

      // Flujo de conversación
      function iniciarConversacion() {
        if (primeraVez) {
          primeraVez = false;
          
          // MOSTRAR MENSAJE DE SALUDO CON AUDIO PREGRABADO
          agregarMensaje('¡Hola! Soy tu perrito ayudante. ¿Necesitas ayuda con algo en la tienda?', false, true, 'audios/saludo.wav');
          
          mostrarOpciones([
            {
              texto: 'Sí, necesito ayuda',
              accion: function() { mostrarOpcionesAyuda(); }
            },
            {
              texto: 'No, solo estoy viendo',
              accion: function() { 
                agregarMensaje('¡De acuerdo! Si cambias de opinión, aquí estaré para ayudarte.');
                setTimeout(() => {
                  chatContainer.style.display = 'none';
                }, 3000);
              }
            }
          ]);
        }
      }

      function mostrarOpcionesAyuda() {
        agregarMensaje('¡Perfecto! ¿En qué puedo ayudarte? Estos son los temas en los que te puedo asistir:');
        
        mostrarOpciones([
          {
            texto: '¿Cómo comprar productos?',
            accion: function() { 
              agregarMensaje('Para comprar es muy fácil:\n\n1. Navega por nuestros productos\n2. Haz clic en "Agregar al carrito"\n3. Ve a tu carrito de compras\n4. Completa tus datos y listo\n\n¿Necesitas más ayuda con esto?');
              mostrarOpcionesSeguimiento('compra');
            }
          },
          {
            texto: 'Métodos de pago y envío',
            accion: function() { 
              agregarMensaje('Aquí tienes la información:\n\n💳 Pagos: Efectivo, transferencia bancaria\n🚚 Envíos: Recogida en tienda o entrega a domicilio\n⏱️ Tiempo: 3-5 días hábiles\n\n¿Te queda alguna duda sobre pagos o envíos?');
              mostrarOpcionesSeguimiento('envios');
            }
          },
          {
            texto: 'Contactar con soporte',
            accion: function() { 
              agregarMensaje('Puedes contactarnos:\n\n📞 Teléfono: +5351435405\n📧 Email: costmadreaguast@gmail.com\n🕒 Horario: Lunes a Viernes 8:00 AM - 5:00 PM\n\n¿Quieres que te ayude con algo más?');
              mostrarOpcionesSeguimiento('contacto');
            }
          }
        ]);
      }

      function mostrarOpcionesSeguimiento(tema) {
        mostrarOpciones([
          {
            texto: 'Sí, más ayuda sobre esto',
            accion: function() { 
              if (tema === 'compra') {
                agregarMensaje('Más detalles sobre compras:\n\n• Puedes filtrar productos por categoría\n• Tenemos materiales de construcción\n• Precios especiales por volumen\n• Asesoramiento técnico incluido\n\n¿Algo específico que quieras saber?');
              } else if (tema === 'envios') {
                agregarMensaje('Más sobre envíos:\n\n📦 Envío gratis en compras mayores a $100\n📍 Cubrimos toda la isla\n📋 Seguimiento de pedido incluido\n🛡️ Garantía en todos los productos\n\n¿Te sirve esta información?');
              } else {
                agregarMensaje('Para contacto inmediato:\n\n• WhatsApp: +5351435405\n• Respondemos emails en 24h\n• Visita nuestra sede\n• Soporte para proyectos PDL\n\n¿En qué más puedo ayudarte?');
              }
              mostrarOpcionesFinales();
            }
          },
          {
            texto: 'No, otra pregunta',
            accion: function() { mostrarOpcionesAyuda(); }
          },
          {
            texto: 'Gracias, ya tengo lo que necesitaba',
            accion: function() { 
              agregarMensaje('Me alegra haber ayudado. Si necesitas algo más, aquí estaré. ¡Que tengas un excelente día!');
              setTimeout(() => {
                chatContainer.style.display = 'none';
              }, 3000);
            }
          }
        ]);
      }

      function mostrarOpcionesFinales() {
        mostrarOpciones([
          {
            texto: 'Hacer otra pregunta',
            accion: function() { mostrarOpcionesAyuda(); }
          },
          {
            texto: 'Finalizar conversación',
            accion: function() { 
              agregarMensaje('Ha sido un gusto ayudarte. Recuerda que estoy aquí cuando me necesites.');
              setTimeout(() => {
                chatContainer.style.display = 'none';
              }, 3000);
            }
          }
        ]);
      }

      // Event Listeners
      asistenteBoton.addEventListener('click', function() {
        if (chatContainer.style.display === 'flex') {
          chatContainer.style.display = 'none';
        } else {
          chatContainer.style.display = 'flex';
          notificacion.style.display = 'none';
          if (primeraVez) {
            setTimeout(iniciarConversacion, 500);
          }
        }
      });

      cerrarChat.addEventListener('click', function() {
        chatContainer.style.display = 'none';
      });

      // Mostrar notificación después de 10 segundos si no han interactuado
      setTimeout(() => {
        if (primeraVez) {
          notificacion.style.display = 'flex';
        }
      }, 10000);

      // Inicializar
      inicializarVoz();
    });
  </script>
</body>
</html>