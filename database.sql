CREATE DATABASE nics_db;
USE nics_db;

CREATE TABLE products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(100) NOT NULL,
    price INT NOT NULL DEFAULT 0,
    quantity INT NOT NULL DEFAULT 10,
    low_stock_notif INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE sales (
    sales_id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount INT NOT NULL,
    payment_amount INT NOT NULL,
    change_amount INT NOT NULL,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sales_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    sales_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price INT NOT NULL,
    subtotal INT NOT NULL,
    FOREIGN KEY (sales_id) REFERENCES sales(sales_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

CREATE TABLE admin_users (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO admin_users (username, password) 
VALUES ('admin', MD5('admin123'));

ALTER TABLE sales_items DROP FOREIGN KEY sales_items_ibfk_2;

ALTER TABLE sales_items 
ADD CONSTRAINT sales_items_ibfk_2 
FOREIGN KEY (product_id) 
REFERENCES products(product_id) 
ON DELETE CASCADE;

ALTER TABLE sales ADD COLUMN payment_type ENUM('cash', 'credit') DEFAULT 'cash';
ALTER TABLE sales ADD COLUMN amount_paid DECIMAL(10,2) DEFAULT 0;
ALTER TABLE sales ADD COLUMN remaining_balance DECIMAL(10,2) DEFAULT 0;
ALTER TABLE sales ADD COLUMN due_date DATE NULL;
ALTER TABLE sales ADD COLUMN status ENUM('paid', 'partial', 'unpaid') DEFAULT 'paid';
ALTER TABLE sales ADD COLUMN customer_name VARCHAR(250) NOT NULL;

CREATE TABLE credit_payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    sales_id INT NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    remarks TEXT,
    FOREIGN KEY (sales_id) REFERENCES sales(sales_id) ON DELETE CASCADE
);

ALTER TABLE products 
    ADD COLUMN category_id INT NULL,
    ADD COLUMN unit VARCHAR(50) DEFAULT 'pcs',
    ADD COLUMN reorder_point INT DEFAULT 5,
    ADD COLUMN location VARCHAR(100) NULL,
    ADD COLUMN last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE products 
ADD CONSTRAINT fk_product_category 
FOREIGN KEY (category_id) REFERENCES categories(category_id) 
ON DELETE SET NULL;

INSERT INTO categories (category_name) VALUES 
   ('Fertilizers'),
   ('Pesticides'),
   ('Seeds'),
   ('Tools & Equipment'),
   ('Animal Feeds');
ON DUPLICATE KEY UPDATE category_name = category_name;

UPDATE products SET category_id = (SELECT category_id FROM categories WHERE category_name = 'Other Supplies' LIMIT 1) 
WHERE category_id IS NULL;

CREATE TABLE inventory_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    quantity_change INT NOT NULL,
    old_quantity INT NOT NULL,
    new_quantity INT NOT NULL,
    remarks TEXT,
    created_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

/* for changing username and pass
UPDATE admin_users 
SET username ='admin',
password = MD5('admin123') 
WHERE username ='raven' */