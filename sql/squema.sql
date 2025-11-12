USE constst_db;

-- Tabla de productos
CREATE TABLE IF NOT EXISTS productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT,
  precio DECIMAL(10,2) NOT NULL,
  stock INT NOT NULL,
  imagen VARCHAR(255)
);

-- Tabla de pedidos
CREATE TABLE IF NOT EXISTS pedidos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_nombre VARCHAR(100) NOT NULL,
  cliente_email VARCHAR(100) NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de detalles del pedido
CREATE TABLE IF NOT EXISTS detalles_pedido (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT NOT NULL,
  producto_id INT NOT NULL,
  cantidad INT NOT NULL,
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
  FOREIGN KEY (producto_id) REFERENCES productos(id)
);

