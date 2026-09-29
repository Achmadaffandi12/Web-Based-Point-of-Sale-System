<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Riwayat Transaksi';
$conn = db_connect();

$search = trim($_GET['search'] ?? '');
$paymentMethod = $_GET['payment_method'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$sql = 'SELECT t.*, u.full_name AS cashier FROM transactions t JOIN users u ON u.id = t.user_id WHERE 1=1';
$params = [];
$types = '';

if ($search !== '') {
    $sql .= ' AND (t.transaction_number LIKE ? OR u.full_name LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}
if ($paymentMethod !== '') {
    $sql .= ' AND t.payment_method = ?';
    $params[] = $paymentMethod;
    $types .= 's';
}
if ($dateFrom !== '') {
    $sql .= ' AND DATE(t.created_at) >= ?';
    $params[] = $dateFrom;
    $types .= 's';
}
if ($dateTo !== '') {
    $sql .= ' AND DATE(t.created_at) <= ?';
    $params[] = $dateTo;
    $types .= 's';
}

$sql .= ' ORDER BY t.created_at DESC';
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$transactions = $stmt->get_result();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="main-panel">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h4 class="mb-1">Riwayat Transaksi</h4>
                <p class="text-muted mb-0">Semua transaksi yang telah diproses</p>
            </div>
        </div>

        <div class="table-card">
            <div class="card-header">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Cari</label>
                        <input type="text" name="search" class="form-control" value="<?php echo safe_output($search); ?>" placeholder="No. transaksi / kasir">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Metode</label>
                        <select name="payment_method" class="form-select">
                            <option value="">Semua</option>
                            <option value="cash" <?php echo $paymentMethod === 'cash' ? 'selected' : ''; ?>>Cash</option>
                            <option value="transfer" <?php echo $paymentMethod === 'transfer' ? 'selected' : ''; ?>>Transfer</option>
                            <option value="qris" <?php echo $paymentMethod === 'qris' ? 'selected' : ''; ?>>QRIS</option>
                            <option value="e-wallet" <?php echo $paymentMethod === 'e-wallet' ? 'selected' : ''; ?>>E-Wallet</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Dari</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo safe_output($dateFrom); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sampai</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo safe_output($dateTo); ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table datatable mb-0">
                        <thead>
                            <tr>
                                <th>No. Transaksi</th>
                                <th>Tanggal</th>
                                <th>Kasir</th>
                                <th>Total</th>
                                <th>Metode</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($transactions && $transactions->num_rows > 0): ?>
                                <?php while ($transaction = $transactions->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo safe_output($transaction['transaction_number']); ?></td>
                                        <td><?php echo format_date_time($transaction['created_at']); ?></td>
                                        <td><?php echo safe_output($transaction['cashier']); ?></td>
                                        <td><?php echo format_rupiah($transaction['total']); ?></td>
                                        <td><?php echo ucfirst(str_replace('-', ' ', $transaction['payment_method'])); ?></td>
                                        <td><span class="badge bg-success-subtle text-success"><?php echo safe_output($transaction['status']); ?></span></td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-outline-primary view-detail" data-id="<?php echo (int) $transaction['id']; ?>">Detail</button>
                                                <a href="receipt.php?id=<?php echo (int) $transaction['id']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Struk</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Belum ada transaksi.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="detailTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="transactionDetailBody"></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script>
document.querySelectorAll('.view-detail').forEach(button => {
    button.addEventListener('click', function () {
        fetch('proses/transaksi.php?action=detail&id=' + this.dataset.id, {
            method: 'GET'
        }).then(response => response.json()).then(result => {
            if (!result.success) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: result.message || 'Detail tidak ditemukan.' });
                return;
            }

            document.getElementById('transactionDetailBody').innerHTML = `
                <div class="row g-3">
                    <div class="col-md-6"><strong>No. Transaksi:</strong> ${result.data.transaction_number}</div>
                    <div class="col-md-6"><strong>Kasir:</strong> ${result.data.cashier}</div>
                    <div class="col-md-6"><strong>Tanggal:</strong> ${result.data.created_at}</div>
                    <div class="col-md-6"><strong>Metode pembayaran:</strong> ${result.data.payment_method}</div>
                    <div class="col-md-6"><strong>Subtotal:</strong> ${result.data.subtotal}</div>
                    <div class="col-md-6"><strong>Diskon:</strong> ${result.data.discount}</div>
                    <div class="col-md-6"><strong>Pajak:</strong> ${result.data.tax}</div>
                    <div class="col-md-6"><strong>Total:</strong> ${result.data.total}</div>
                </div>
                <hr>
                <table class="table table-sm">
                    <thead>
                        <tr><th>Produk</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr>
                    </thead>
                    <tbody>
                        ${result.data.items.map(item => `
                            <tr>
                                <td>${item.product_name}</td>
                                <td>${item.quantity}</td>
                                <td>${item.price}</td>
                                <td>${item.subtotal}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            new bootstrap.Modal(document.getElementById('detailTransactionModal')).show();
        });
    });
});
</script>
<?php

