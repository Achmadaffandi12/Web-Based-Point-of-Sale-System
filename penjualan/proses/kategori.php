<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['action'])) {
    redirect('kategori.php');
}

$conn = db_connect();

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            set_flash('success', 'Kategori berhasil dihapus.');
        } else {
            set_flash('error', 'Gagal menghapus kategori.');
        }
    }
    redirect('../kategori.php');
}

$name = trim($_POST['name'] ?? '');
$action = $_POST['action'] ?? 'create';
$id = (int) ($_POST['id'] ?? 0);

if ($name === '') {
    set_flash('error', 'Nama kategori wajib diisi.');
    redirect('../kategori.php');
}

if ($action === 'update' && $id > 0) {
    $stmt = $conn->prepare('UPDATE categories SET name = ? WHERE id = ?');
    $stmt->bind_param('si', $name, $id);
    if ($stmt->execute()) {
        set_flash('success', 'Kategori berhasil diperbarui.');
    } else {
        set_flash('error', 'Gagal memperbarui kategori.');
    }
} else {
    $stmt = $conn->prepare('INSERT INTO categories (name) VALUES (?)');
    $stmt->bind_param('s', $name);
    if ($stmt->execute()) {
        set_flash('success', 'Kategori berhasil ditambahkan.');
    } else {
        set_flash('error', 'Gagal menambahkan kategori.');
    }
}

redirect('../kategori.php');
