<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$transactionId = (int) ($_GET['id'] ?? 0);
$conn = db_connect();
$transaction = $conn->prepare('SELECT t.*, u.full_name AS cashier FROM transactions t JOIN users u ON u.id = t.user_id WHERE t.id = ? LIMIT 1');
$transaction->bind_param('i', $transactionId);
$transaction->execute();
$transactionData = $transaction->get_result()->fetch_assoc();
if (!$transactionData) {
    redirect('riwayat.php');
}

$details = $conn->prepare('SELECT * FROM transaction_details WHERE transaction_id = ? ORDER BY id ASC');
$details->bind_param('i', $transactionId);
$details->execute();
$detailRows = $details->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk <?php echo safe_output($transactionData['transaction_number']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: monospace; background: #f8fafc; padding: 25px; }
        .receipt { width: 320px; background: white; margin: 0 auto; border: 1px solid #ddd; border-radius: 12px; padding: 20px; }
        .title { font-size: 22px; font-weight: 700; text-align: center; }
        table { width: 100%; font-size: 12px; }
        .divider { border-top: 1px dashed #999; margin: 10px 0; }
        .totals td { padding: 4px 0; }
        .small { font-size: 11px; }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="title">POS MODERN</div>
        <div class="text-center small mb-3">Jl. Merdeka No. 10, Jakarta</div>
        <div class="small">No. Transaksi: <?php echo safe_output($transactionData['transaction_number']); ?></div>
        <div class="small">Tanggal: <?php echo format_date_time($transactionData['created_at']); ?></div>
        <div class="small">Kasir: <?php echo safe_output($transactionData['cashier']); ?></div>
        <div class="divider"></div>
        <table>
            <tr><th>Produk</th><th class="text-center">Qty</th><th class="text-end">Harga</th></tr>
            <?php while ($detail = $detailRows->fetch_assoc()): ?>
                <tr>
                    <td><?php echo safe_output($detail['product_name']); ?></td>
                    <td class="text-center"><?php echo (int) $detail['quantity']; ?></td>
                    <td class="text-end"><?php echo format_rupiah($detail['subtotal']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
        <div class="divider"></div>
        <table class="totals">
            <tr><td>Subtotal</td><td class="text-end"><?php echo format_rupiah($transactionData['subtotal']); ?></td></tr>
            <tr><td>Diskon</td><td class="text-end"><?php echo format_rupiah($transactionData['discount']); ?></td></tr>
            <tr><td>Pajak</td><td class="text-end"><?php echo format_rupiah($transactionData['tax']); ?></td></tr>
            <tr><td><strong>Total</strong></td><td class="text-end"><strong><?php echo format_rupiah($transactionData['total']); ?></strong></td></tr>
            <tr><td>Bayar</td><td class="text-end"><?php echo format_rupiah($transactionData['payment_amount']); ?></td></tr>
            <tr><td>Kembali</td><td class="text-end"><?php echo format_rupiah($transactionData['change_amount']); ?></td></tr>
        </table>
        <div class="divider"></div>
        <div class="text-center small mt-2">Terima kasih telah berbelanja.</div>
    </div>
</body>
</html>
