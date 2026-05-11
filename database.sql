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

CREATE TABLE IF NOT EXISTS admin_users (
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
<--for changing username and pass-->
UPDATE admin_users 
SET username ='admin',
password = MD5('admin123') 
WHERE username ='raven'