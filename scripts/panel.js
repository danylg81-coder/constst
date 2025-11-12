        // Vista previa de imagen
        function previewImage(input) {
            const preview = document.getElementById('image-preview');
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        }

        // Función para eliminar producto
        function eliminarProducto(id, nombre) {
            if (confirm(`¿Estás seguro de que deseas eliminar el producto "${nombre}"?`)) {
                fetch('eliminar_producto.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Producto eliminado exitosamente');
                        location.reload();
                    } else {
                        alert('Error al eliminar el producto: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar el producto');
                });
            }
        }

        // Función para editar producto (placeholder)
        function editarProducto(id) {
            alert(`Funcionalidad de edición para el producto ID: ${id}\nEsta funcionalidad se implementará próximamente.`);
            // Aquí puedes redirigir a un formulario de edición o abrir un modal
            // window.location.href = `editar_producto.php?id=${id}`;
        }

        // Validación del formulario
        document.getElementById('form-producto').addEventListener('submit', function(e) {
            const precio = document.getElementById('precio').value;
            const stock = document.getElementById('stock').value;
            
            if (precio <= 0) {
                alert('El precio debe ser mayor a 0');
                e.preventDefault();
                return;
            }
            
            if (stock < 0) {
                alert('El stock no puede ser negativo');
                e.preventDefault();
                return;
            }
        });
    
        function previewImage(input) {
            const preview = document.getElementById('image-preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        

        // Prevenir reenvío del formulario al recargar la página
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        