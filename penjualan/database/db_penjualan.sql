CREATE DATABASE IF NOT EXISTS `db_penjualan` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_penjualan`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'cashier') NOT NULL DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_users_username` (`username`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_categories_name` (`name`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `products` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `buy_price` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `sell_price` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `stock` INT NOT NULL DEFAULT 0,
    `unit` VARCHAR(50) NOT NULL DEFAULT 'pcs',
    `status` ENUM('active', 'inactive', 'draft') NOT NULL DEFAULT 'active',
    `image` VARCHAR(255) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_products_category` (`category_id`),
    INDEX `idx_products_name` (`name`),
    CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `transaction_number` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT UNSIGNED NOT NULL,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `discount` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `tax` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `total` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `payment_method` ENUM('cash', 'transfer', 'qris', 'e-wallet') NOT NULL,
    `payment_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `change_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `status` ENUM('completed', 'pending', 'cancelled') NOT NULL DEFAULT 'completed',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_transactions_user` (`user_id`),
    INDEX `idx_transactions_created` (`created_at`),
    CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `transaction_details` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `transaction_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `product_name` VARCHAR(150) NOT NULL,
    `quantity` INT NOT NULL,
    `price` DECIMAL(12,2) NOT NULL,
    `subtotal` DECIMAL(12,2) NOT NULL,
    `profit` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_transaction_details_transaction` (`transaction_id`),
    INDEX `idx_transaction_details_product` (`product_id`),
    CONSTRAINT `fk_transaction_details_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_transaction_details_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `stock_movements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT UNSIGNED NOT NULL,
    `movement_type` ENUM('in', 'out', 'adjustment') NOT NULL,
    `quantity` INT NOT NULL,
    `stock_before` INT NOT NULL,
    `stock_after` INT NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_stock_product` (`product_id`),
    INDEX `idx_stock_user` (`user_id`),
    CONSTRAINT `fk_stock_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO `users` (`username`, `password`, `full_name`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin'),
('kasir1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir Satu', 'cashier');

INSERT INTO `categories` (`name`) VALUES
('Makanan'),
('Minuman'),
('Elektronik'),
('Perawatan'),
('ATK');

INSERT INTO `products` (`code`, `name`, `category_id`, `buy_price`, `sell_price`, `stock`, `unit`, `status`, `description`) VALUES
('P001', 'Kopi Hitam Premium', 2, 7000, 15000, 25, 'pcs', 'active', 'Kopi bubuk premium 250g'),
('P002', 'Teh Botol 600ml', 2, 4500, 9000, 40, 'pcs', 'active', 'Minuman teh botol isi 600ml'),
('P003', 'Biskuit Cokelat', 1, 5000, 12000, 18, 'pcs', 'active', 'Biskuit renyah dengan lapisan cokelat'),
('P004', 'Susu UHT Cokelat', 2, 6500, 14000, 30, 'pcs', 'active', 'Susu UHT rasa cokelat 250ml'),
('P005', 'Keyboard Wireless', 3, 120000, 220000, 12, 'pcs', 'active', 'Keyboard tanpa kabel ultra ringkas'),
('P006', 'Mouse Gaming', 3, 90000, 180000, 17, 'pcs', 'active', 'Mouse gaming dengan sensor tinggi'),
('P007', 'Sabun Cuci Piring', 4, 8000, 17000, 28, 'pcs', 'active', 'Sabun pembersih serbaguna'),
('P008', 'Tissue Basah', 4, 6000, 13000, 14, 'pcs', 'active', 'Tissue basah 10 lembar'),
('P009', 'Pensil 2B', 5, 2000, 5000, 50, 'pcs', 'active', 'Pensil standar sekolah & kantor'),
('P010', 'Buku Tulis 40L', 5, 4000, 9000, 35, 'pcs', 'active', 'Buku tulis 40 lembar'),
('P011', 'Nasi Box Ayam', 1, 12000, 22000, 20, 'pcs', 'active', 'Paket nasi box dengan ayam'),
('P012', 'Keripik Kentang', 1, 7000, 15000, 22, 'pcs', 'active', 'Keripik kentang rasa original');

INSERT INTO `transactions` (`transaction_number`, `user_id`, `subtotal`, `discount`, `tax`, `total`, `payment_method`, `payment_amount`, `change_amount`, `status`) VALUES
('TRX-20260927-0001', 1, 34000, 0, 0, 34000, 'cash', 40000, 6000, 'completed'),
('TRX-20260927-0002', 2, 22000, 1000, 0, 21000, 'qris', 21000, 0, 'completed');

INSERT INTO `transaction_details` (`transaction_id`, `product_id`, `product_name`, `quantity`, `price`, `subtotal`, `profit`) VALUES
(1, 3, 'Biskuit Cokelat', 2, 12000, 24000, 14000),
(1, 2, 'Teh Botol 600ml', 1, 9000, 9000, 4500),
(1, 9, 'Pensil 2B', 1, 5000, 1000, 3000),
(2, 10, 'Buku Tulis 40L', 2, 9000, 18000, 10000),
(2, 8, 'Tissue Basah', 1, 13000, 13000, 7000);

INSERT INTO `stock_movements` (`product_id`, `movement_type`, `quantity`, `stock_before`, `stock_after`, `description`, `user_id`) VALUES
(3, 'out', 2, 20, 18, 'Penjualan transaksi TRX-20260927-0001', 1),
(2, 'out', 1, 41, 40, 'Penjualan transaksi TRX-20260927-0001', 1),
(9, 'out', 1, 51, 50, 'Penjualan transaksi TRX-20260927-0001', 1),
(10, 'out', 2, 37, 35, 'Penjualan transaksi TRX-20260927-0002', 2),
(8, 'out', 1, 15, 14, 'Penjualan transaksi TRX-20260927-0002', 2);
