
        // Habilitar botón de confirmación cuando se marque el checkbox y todos los campos estén llenos
        const form = document.getElementById('formPagoEfectivo');
        const checkbox = document.getElementById('confirmar_datos');
        const btnConfirmar = document.getElementById('btnConfirmar');

        function validarFormulario() {
            const campos = form.querySelectorAll('input[required]');
            let todosLlenos = true;
            
            campos.forEach(campo => {
                if (!campo.value.trim()) {
                    todosLlenos = false;
                    campo.style.borderColor = '#dc3545';
                } else {
                    campo.style.borderColor = '#28a745';
                }
            });

            btnConfirmar.disabled = !(checkbox.checked && todosLlenos);
        }

        // Validar en tiempo real
        form.addEventListener('input', validarFormulario);
        checkbox.addEventListener('change', validarFormulario);

        // Mostrar alerta de confirmación antes de enviar el formulario
        form.addEventListener('submit', function(e) {
            if (!confirm('¿Estás seguro de que quieres confirmar tu pedido? Se generará una factura y podrás recogerlo en la tienda.')) {
                e.preventDefault();
            }
        });

        // Validar formulario al cargar la página
        document.addEventListener('DOMContentLoaded', validarFormulario);
        
        // Efecto visual al completar campos
        const inputs = form.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.style.borderColor = '#28a745';
                    this.style.backgroundColor = '#f8fff9';
                } else {
                    this.style.borderColor = '#e0e0e0';
                    this.style.backgroundColor = 'white';
                }
            });
        });

       