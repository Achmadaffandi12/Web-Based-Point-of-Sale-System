<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'db_penjualan';

function db_connect(): mysqli
{
    static $connection = null;

    if ($connection === null) {
        $connection = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($connection->connect_error) {
            throw new RuntimeException('Database connection failed: ' . $connection->connect_error);
        }

        $connection->set_charset('utf8mb4');
    }

    return $connection;
}

function db_connection_message(): string
{
    return 'Database belum aktif atau belum di-import. Jalankan MySQL/XAMPP lalu import file SQL db_penjualan.sql.';
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function safe_output($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function ensure_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    try {
        $conn = db_connect();
        $stmt = $conn->prepare('SELECT id, username, full_name, role FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function format_rupiah($value): string
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

function get_product_status_badge(string $status): string
{
    $classes = [
        'active' => 'bg-success-subtle text-success',
        'inactive' => 'bg-secondary-subtle text-secondary',
        'draft' => 'bg-warning-subtle text-warning',
    ];

    $label = [
        'active' => 'Aktif',
        'inactive' => 'Nonaktif',
        'draft' => 'Draft',
    ];

    return '<span class="badge rounded-pill ' . ($classes[$status] ?? 'bg-secondary-subtle text-secondary') . '">' . ($label[$status] ?? ucfirst($status)) . '</span>';
}

function get_stock_label(int $stock): string
{
    if ($stock <= 0) {
        return '<span class="badge bg-danger-subtle text-danger">Habis</span>';
    }
    if ($stock <= 5) {
        return '<span class="badge bg-warning-subtle text-warning">Menipis</span>';
    }

    return '<span class="badge bg-success-subtle text-success">Aman</span>';
}

function generate_transaction_number(): string
{
    try {
        $conn = db_connect();
        $date = date('Ymd');
        $result = $conn->query("SELECT COUNT(*) AS total FROM transactions WHERE DATE(created_at) = CURDATE()");
        $count = $result && $result->num_rows ? (int) $result->fetch_assoc()['total'] : 0;
        $sequence = $count + 1;

        return 'TRX-' . $date . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    } catch (Throwable $e) {
        return 'TRX-' . date('Ymd') . '-0001';
    }
}

function format_date_time(string $datetime): string
{
    return date('d M Y, H:i', strtotime($datetime));
}

function format_date(string $date): string
{
    return date('d M Y', strtotime($date));
}

function get_last_error(mysqli $conn): string
{
    return $conn->error ?: 'Unknown database error';
}

function get_stats(): array
{
    $stats = [
        'products' => 0,
        'categories' => 0,
        'transactions' => 0,
        'revenue' => 0,
        'sold_today' => 0,
        'low_stock' => 0,
    ];

    try {
        $conn = db_connect();
        $stats['products'] = (int) $conn->query('SELECT COUNT(*) AS total FROM products')->fetch_assoc()['total'];
        $stats['categories'] = (int) $conn->query('SELECT COUNT(*) AS total FROM categories')->fetch_assoc()['total'];
        $stats['transactions'] = (int) $conn->query('SELECT COUNT(*) AS total FROM transactions')->fetch_assoc()['total'];
        $revenue = $conn->query('SELECT COALESCE(SUM(total), 0) AS total FROM transactions WHERE status = "completed"');
        $stats['revenue'] = (float) ($revenue->fetch_assoc()['total'] ?? 0);
        $soldToday = $conn->query("SELECT COALESCE(SUM(quantity), 0) AS total FROM transaction_details WHERE DATE(created_at) = CURDATE()");
        $stats['sold_today'] = (int) ($soldToday->fetch_assoc()['total'] ?? 0);
        $lowStock = $conn->query('SELECT COUNT(*) AS total FROM products WHERE stock <= 5');
        $stats['low_stock'] = (int) ($lowStock->fetch_assoc()['total'] ?? 0);
    } catch (Throwable $e) {
        return $stats;
    }

    return $stats;
}
