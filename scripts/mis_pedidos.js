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
                
                // Cerrar dropdown al hacer clic en un item (opcional)
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
        });
    