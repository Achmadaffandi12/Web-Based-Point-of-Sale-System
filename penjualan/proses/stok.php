<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../stok.php');
}

$conn = db_connect();
$productId = (int) ($_POST['product_id'] ?? 0);
$movementType = $_POST['movement_type'] ?? 'adjustment';
$quantity = (int) ($_POST['quantity'] ?? 0);
$description = trim($_POST['description'] ?? '');
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($productId <= 0 || $quantity <= 0 || $userId <= 0) {
    set_flash('error', 'Data stok tidak valid.');
    redirect('../stok.php');
}

$product = $conn->prepare('SELECT stock, name FROM products WHERE id = ? LIMIT 1');
$product->bind_param('i', $productId);
$product->execute();
$productData = $product->get_result()->fetch_assoc();
if (!$productData) {
    set_flash('error', 'Produk tidak ditemukan.');
    redirect('../stok.php');
}

$stockBefore = (int) $productData['stock'];
$newStock = $stockBefore;
if ($movementType === 'in') {
    $newStock = $stockBefore + $quantity;
} elseif ($movementType === 'out') {
    $newStock = max($stockBefore - $quantity, 0);
} else {
    $newStock = $quantity;
}

$stmt = $conn->prepare('UPDATE products SET stock = ? WHERE id = ?');
$stmt->bind_param('ii', $newStock, $productId);
if (!$stmt->execute()) {
    set_flash('error', 'Gagal memperbarui stok produk.');
    redirect('../stok.php');
}

$movementStmt = $conn->prepare('INSERT INTO stock_movements (product_id, movement_type, quantity, stock_before, stock_after, description, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
$movementStmt->bind_param('isiiisi', $productId, $movementType, $quantity, $stockBefore, $newStock, $description, $userId);
if ($movementStmt->execute()) {
    set_flash('success', 'Stok berhasil diperbarui.');
} else {
    set_flash('error', 'Gagal mencatat riwayat stok.');
}

redirect('../stok.php');
