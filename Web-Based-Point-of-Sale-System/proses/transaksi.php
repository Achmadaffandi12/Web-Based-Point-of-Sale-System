<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$conn = db_connect();
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['cart'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Keranjang transaksi tidak valid.']);
    exit;
}

$userId = (int) ($_SESSION['user_id'] ?? 1);
$cart = $data['cart'];
$discount = (float) ($data['discount'] ?? 0);
$tax = (float) ($data['tax'] ?? 0);
$paymentMethod = $data['payment_method'] ?? 'cash';
$paymentAmount = (float) ($data['payment_amount'] ?? 0);

$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 0);
}

$total = max($subtotal - $discount + $tax, 0);
if ($paymentAmount < $total) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Pembayaran kurang dari total transaksi.']);
    exit;
}

$conn->begin_transaction();
try {
    $transactionNumber = generate_transaction_number();
    $stmt = $conn->prepare('INSERT INTO transactions (transaction_number, user_id, subtotal, discount, tax, total, payment_method, payment_amount, change_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $change = $paymentAmount - $total;
    $status = 'completed';
    $stmt->bind_param('siidddssds', $transactionNumber, $userId, $subtotal, $discount, $tax, $total, $paymentMethod, $paymentAmount, $change, $status);
    if (!$stmt->execute()) {
        throw new RuntimeException('Gagal menyimpan transaksi utama.');
    }
    $transactionId = $conn->insert_id;

    foreach ($cart as $item) {
        $productId = (int) ($item['id'] ?? 0);
        $quantity = (int) ($item['quantity'] ?? 0);
        $price = (float) ($item['price'] ?? 0);
        $productStmt = $conn->prepare('SELECT id, name, buy_price, sell_price, stock FROM products WHERE id = ? LIMIT 1');
        $productStmt->bind_param('i', $productId);
        $productStmt->execute();
        $product = $productStmt->get_result()->fetch_assoc();

        if (!$product) {
            throw new RuntimeException('Produk tidak ditemukan.');
        }
        if ($quantity > (int) $product['stock']) {
            throw new RuntimeException('Stok produk ' . $product['name'] . ' tidak cukup.');
        }

        $lineSubtotal = $price * $quantity;
        $profit = (($product['sell_price'] - $product['buy_price']) * $quantity);

        $detailStmt = $conn->prepare('INSERT INTO transaction_details (transaction_id, product_id, product_name, quantity, price, subtotal, profit) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $detailStmt->bind_param('iisiidd', $transactionId, $productId, $product['name'], $quantity, $price, $lineSubtotal, $profit);
        if (!$detailStmt->execute()) {
            throw new RuntimeException('Gagal menyimpan detail transaksi.');
        }

        $newStock = (int) $product['stock'] - $quantity;
        $updateProductStmt = $conn->prepare('UPDATE products SET stock = ? WHERE id = ?');
        $updateProductStmt->bind_param('ii', $newStock, $productId);
        if (!$updateProductStmt->execute()) {
            throw new RuntimeException('Gagal mengurangi stok produk.');
        }

        $movementStmt = $conn->prepare('INSERT INTO stock_movements (product_id, movement_type, quantity, stock_before, stock_after, description, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $movementType = 'out';
        $movementStmt->bind_param('isiiisi', $productId, $movementType, $quantity, $product['stock'], $newStock, $transactionNumber, $userId);
        if (!$movementStmt->execute()) {
            throw new RuntimeException('Gagal mencatat pergerakan stok.');
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'transaction_id' => $transactionId, 'transaction_number' => $transactionNumber, 'change_amount' => $change]);
} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
