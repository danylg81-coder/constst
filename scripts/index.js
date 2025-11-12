// Menú Circular - Solución definitiva
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado - Inicializando menú circular');
    
    const circularToggle = document.getElementById('circular-toggle');
    const circularButton = document.querySelector('.circular-menu-button');
    
    if (circularToggle && circularButton) {
        console.log('✅ Elementos del menú circular encontrados');

        // Manejar clic en el botón del menú
        circularButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Cambiar el estado del toggle manualmente
            circularToggle.checked = !circularToggle.checked;
            console.log('🔄 Toggle cambiado a:', circularToggle.checked);
        });

        // Cerrar menú al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (circularToggle.checked && 
                !circularButton.contains(e.target) && 
                !e.target.closest('.circular-bubble')) {
                circularToggle.checked = false;
                console.log('❌ Menú cerrado (clic fuera)');
            }
        });

        // Manejar clic en las burbujas
        const bubbleContents = document.querySelectorAll('.bubble-content');
        bubbleContents.forEach(bubble => {
            bubble.addEventListener('click', function(e) {
                console.log('📍 Navegando a:', this.getAttribute('href'));
                // No cerramos inmediatamente, dejamos que el enlace se ejecute
            });
        });

        // Prevenir que los clics en las burbujas cierren el menú
        const circularBubbles = document.querySelectorAll('.circular-bubble');
        circularBubbles.forEach(bubble => {
            bubble.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });

    } else {
        console.error('❌ Elementos del menú circular NO encontrados');
        if (!circularToggle) console.error('No se encontró #circular-toggle');
        if (!circularButton) console.error('No se encontró .circular-menu-button');
    }
});

// El resto de tu código permanece igual...
// Funcionalidad del formulario de contacto
const form = document.getElementById('contactForm');
if (form) {
    const inputs = form.querySelectorAll('input, textarea, select');
    
    // Efectos de focus en los inputs
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.style.borderColor = '#0B3A66';
            this.style.boxShadow = '0 0 0 3px rgba(11, 58, 102, 0.1)';
        });
        
        input.addEventListener('blur', function() {
            this.style.borderColor = '#E5E7EB';
            this.style.boxShadow = 'none';
        });
    });
    
    // Validación en tiempo real
    form.addEventListener('submit', function(e) {
        let valid = true;
        const nombre = form.querySelector('input[name="nombre"]');
        const email = form.querySelector('input[name="email"]');
        const mensaje = form.querySelector('textarea[name="mensaje"]');
        
        // Reset estilos
        [nombre, email, mensaje].forEach(field => {
            field.style.borderColor = '#E5E7EB';
        });
        
        if (!nombre.value.trim()) {
            nombre.style.borderColor = '#DC2626';
            valid = false;
        }
        
        if (!email.value.trim() || !isValidEmail(email.value)) {
            email.style.borderColor = '#DC2626';
            valid = false;
        }
        
        if (!mensaje.value.trim() || mensaje.value.length < 10) {
            mensaje.style.borderColor = '#DC2626';
            valid = false;
        }
        
        if (!valid) {
            e.preventDefault();
            // Scroll al primer error
            const firstError = form.querySelector('[style*="border-color: rgb(220, 38, 38)"]');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
    
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
}

// Sistema de countdown para el mensaje de éxito
const successMessage = document.getElementById('success-message');
if (successMessage) {
    let seconds = 5;
    const countdownElement = document.getElementById('countdown-timer');
    
    const countdown = setInterval(function() {
        seconds--;
        if (countdownElement) {
            countdownElement.textContent = seconds + 's';
        }
        
        if (seconds <= 0) {
            clearInterval(countdown);
            successMessage.style.opacity = '0';
            successMessage.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 500);
        }
    }, 1000);
}

// Enfocar el primer campo si hay errores
if (document.querySelector('#error-message')) {
    const el = document.querySelector('input[name="nombre"]');
    if (el) el.focus();
}

// Carrusel de imágenes
class Carrusel {
  constructor() {
    this.carrusel = document.querySelector('.carrusel');
    this.slides = document.querySelectorAll('.slide');
    this.indicadores = document.querySelector('.indicadores');
    this.indiceActual = 0;
    this.totalSlides = this.slides.length;
    this.intervalo = null;
    this.velocidad = 4000;
    
    if (this.carrusel && this.slides.length > 0) {
        this.inicializar();
    }
  }
  
  inicializar() {
    this.crearIndicadores();
    this.crearControles();
    this.iniciarAutoDeslizamiento();
    this.actualizarCarrusel();
    this.agregarEventos();
  }
  
  crearIndicadores() {
    if (!this.indicadores) return;
    
    for (let i = 0; i < this.totalSlides; i++) {
      const indicador = document.createElement('div');
      indicador.className = 'indicador';
      if (i === 0) indicador.classList.add('activo');
      indicador.addEventListener('click', () => this.irASlide(i));
      this.indicadores.appendChild(indicador);
    }
  }
  
  crearControles() {
    const controlesHTML = `
      <div class="controles-carrusel">
        <button class="btn-anterior">‹</button>
        <button class="btn-siguiente">›</button>
      </div>
    `;
    const galeria = document.querySelector('.galeria');
    if (galeria) {
        galeria.insertAdjacentHTML('beforeend', controlesHTML);
        
        const btnAnterior = galeria.querySelector('.btn-anterior');
        const btnSiguiente = galeria.querySelector('.btn-siguiente');
        
        if (btnAnterior) btnAnterior.addEventListener('click', () => this.slideAnterior());
        if (btnSiguiente) btnSiguiente.addEventListener('click', () => this.slideSiguiente());
    }
  }
  
  agregarEventos() {
    this.carrusel.addEventListener('mouseenter', () => this.pausarAutoDeslizamiento());
    this.carrusel.addEventListener('mouseleave', () => this.reanudarAutoDeslizamiento());
    
    document.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowLeft') this.slideAnterior();
      if (e.key === 'ArrowRight') this.slideSiguiente();
      if (e.key === 'Escape') cerrarModal();
    });
  }
  
  actualizarCarrusel() {
    const translateX = -this.indiceActual * 100;
    this.carrusel.style.transform = `translateX(${translateX}%)`;
    
    document.querySelectorAll('.indicador').forEach((ind, index) => {
      ind.classList.toggle('activo', index === this.indiceActual);
    });
  }
  
  slideSiguiente() {
    this.indiceActual = (this.indiceActual + 1) % this.totalSlides;
    this.actualizarCarrusel();
  }
  
  slideAnterior() {
    this.indiceActual = (this.indiceActual - 1 + this.totalSlides) % this.totalSlides;
    this.actualizarCarrusel();
  }
  
  irASlide(indice) {
    this.indiceActual = indice;
    this.actualizarCarrusel();
  }
  
  iniciarAutoDeslizamiento() {
    this.intervalo = setInterval(() => this.slideSiguiente(), this.velocidad);
  }
  
  pausarAutoDeslizamiento() {
    if (this.intervalo) {
      clearInterval(this.intervalo);
      this.intervalo = null;
    }
  }
  
  reanudarAutoDeslizamiento() {
    if (!this.intervalo) {
      this.iniciarAutoDeslizamiento();
    }
  }
}

// Funciones del modal
function abrirModal(img) {
  const modal = document.getElementById('modal');
  const imagenAmpliada = document.getElementById('imagenAmpliada');
  
  if (!modal || !imagenAmpliada) return;
  
  imagenAmpliada.src = img.src;
  imagenAmpliada.alt = img.alt || 'Imagen ampliada';
  
  modal.style.display = 'flex';
  setTimeout(() => {
    modal.classList.add('mostrar');
  }, 10);
  
  if (window.carrusel) {
    window.carrusel.pausarAutoDeslizamiento();
  }
}

function cerrarModal() {
  const modal = document.getElementById('modal');
  if (!modal) return;
  
  modal.classList.remove('mostrar');
  setTimeout(() => {
    modal.style.display = 'none';
  }, 300);
  
  if (window.carrusel) {
    window.carrusel.reanudarAutoDeslizamiento();
  }
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('modal');
  if (modal) {
    modal.addEventListener('click', function(e) {
      if (e.target === modal) {
        cerrarModal();
      }
    });
  }
  
  // Inicializar carrusel
  window.carrusel = new Carrusel();
});