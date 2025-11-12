
        // Generador de partículas para el fondo
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('particlesContainer');
            const particleCount = 20;

            for (let i = 0; i < particleCount; i++) {
                createParticle(container);
            }

            function createParticle(container) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Tamaño aleatorio
                const size = Math.random() * 8 + 2;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Posición inicial aleatoria
                particle.style.left = `${Math.random() * 100}%`;
                
                // Duración y delay aleatorios
                const duration = Math.random() * 20 + 10;
                const delay = Math.random() * 5;
                particle.style.animationDuration = `${duration}s`;
                particle.style.animationDelay = `${delay}s`;
                
                container.appendChild(particle);

                // Reiniciar partícula cuando termine la animación
                setTimeout(() => {
                    particle.remove();
                    createParticle(container);
                }, (duration + delay) * 1000);
            }

            // Efecto de interacción con el formulario
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
        });
    