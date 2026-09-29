<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['action'])) {
    redirect('../produk.php');
}

$conn = db_connect();

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            set_flash('success', 'Produk berhasil dihapus.');
        } else {
            set_flash('error', 'Gagal menghapus produk.');
        }
    }
    redirect('../produk.php');
}

$action = $_POST['action'] ?? 'create';
$id = (int) ($_POST['id'] ?? 0);
$code = trim($_POST['code'] ?? '');
$name = trim($_POST['name'] ?? '');
$categoryId = (int) ($_POST['category_id'] ?? 0);
$buyPrice = (float) ($_POST['buy_price'] ?? 0);
$sellPrice = (float) ($_POST['sell_price'] ?? 0);
$stock = (int) ($_POST['stock'] ?? 0);
$unit = trim($_POST['unit'] ?? 'pcs');
$status = $_POST['status'] ?? 'active';
$description = trim($_POST['description'] ?? '');

if ($code === '' || $name === '' || $categoryId <= 0 || !is_numeric($_POST['buy_price'] ?? null) || !is_numeric($_POST['sell_price'] ?? null) || $stock < 0) {
    set_flash('error', 'Kode produk, nama produk, kategori, harga, serta stok wajib valid.');
    redirect('../produk.php');
}

if ($action === 'update' && $id > 0) {
    $stmt = $conn->prepare('UPDATE products SET code = ?, name = ?, category_id = ?, buy_price = ?, sell_price = ?, stock = ?, unit = ?, status = ?, description = ?, updated_at = NOW() WHERE id = ?');
    $stmt->bind_param('ssiddisisi', $code, $name, $categoryId, $buyPrice, $sellPrice, $stock, $unit, $status, $description, $id);
    if ($stmt->execute()) {
        set_flash('success', 'Produk berhasil diperbarui.');
    } else {
        set_flash('error', 'Gagal memperbarui produk.');
    }
} else {
    $stmt = $conn->prepare('INSERT INTO products (code, name, category_id, buy_price, sell_price, stock, unit, status, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssiddisss', $code, $name, $categoryId, $buyPrice, $sellPrice, $stock, $unit, $status, $description);
    if ($stmt->execute()) {
        set_flash('success', 'Produk berhasil ditambahkan.');
    } else {
        set_flash('error', 'Gagal menambahkan produk.');
    }
}

redirect('../produk.php');
