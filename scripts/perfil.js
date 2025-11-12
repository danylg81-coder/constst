
        document.addEventListener('DOMContentLoaded', function() {
            const userToggle = document.getElementById('userToggle');
            const userDropdown = document.getElementById('userDropdown');
            
            if (userToggle && userDropdown) {
                // Toggle dropdown
                userToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('show');
                    userToggle.classList.toggle('active');
                });
                
                // Cerrar dropdown al hacer clic fuera
                document.addEventListener('click', function(e) {
                    if (!userToggle.contains(e.target) && !userDropdown.contains(e.target)) {
                        userDropdown.classList.remove('show');
                        userToggle.classList.remove('active');
                    }
                });
                
                // Cerrar dropdown al hacer clic en un item
                userDropdown.addEventListener('click', function(e) {
                    if (e.target.tagName === 'A' || e.target.closest('a')) {
                        setTimeout(() => {
                            userDropdown.classList.remove('show');
                            userToggle.classList.remove('active');
                        }, 300);
                    }
                });
                
                // Prevenir cierre cuando se hace clic dentro del dropdown
                userDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }

            // Validación de contraseña en tiempo real
            const passwordInput = document.getElementById('password');
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    if (this.value.length > 0 && this.value.length < 6) {
                        this.style.borderColor = '#e74c3c';
                    } else {
                        this.style.borderColor = this.value.length >= 6 ? '#27ae60' : '#e0e0e0';
                    }
                });
            }
        });
    