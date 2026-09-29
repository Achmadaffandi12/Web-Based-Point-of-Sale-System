<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Laporan';
$conn = db_connect();

$range = $_GET['range'] ?? 'month';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

$where = "WHERE status = 'completed'";
if ($range === 'today') {
    $where .= " AND DATE(created_at) = CURDATE()";
} elseif ($range === 'week') {
    $where .= " AND YEARWEEK(created_at) = YEARWEEK(CURDATE())";
} elseif ($range === 'month') {
    $where .= " AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
} elseif ($range === 'year') {
    $where .= " AND YEAR(created_at) = YEAR(CURDATE())";
} elseif ($range === 'custom') {
    $where .= " AND DATE(created_at) BETWEEN ? AND ?";
}

$sql = 'SELECT * FROM transactions ' . $where . ' ORDER BY created_at DESC';
$stmt = $conn->prepare($sql);
if ($range === 'custom') {
    $stmt->bind_param('ss', $dateFrom, $dateTo);
}
$stmt->execute();
$transactions = $stmt->get_result();

$sumResult = $conn->query("SELECT COALESCE(SUM(total), 0) AS total, COALESCE(SUM((SELECT SUM(quantity) FROM transaction_details WHERE transaction_id = transactions.id)), 0) AS qty, COALESCE(SUM((SELECT SUM(profit) FROM transaction_details WHERE transaction_id = transactions.id)), 0) AS profit FROM transactions WHERE status = 'completed' AND DATE(created_at) BETWEEN '$dateFrom' AND '$dateTo'");
$summary = $sumResult->fetch_assoc();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="main-panel">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h4 class="mb-1">Laporan</h4>
                <p class="text-muted mb-0">Ringkasan penjualan dan laba</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" onclick="window.print()"><i class="fa-solid fa-print me-2"></i>Print</button>
                <a class="btn btn-outline-success" href="data:text/csv;charset=utf-8,<?php echo rawurlencode("No. Transaksi,Tanggal,Total\n"); ?>" download="laporan_penjualan.csv"><i class="fa-solid fa-file-excel me-2"></i>Export Excel</a>
                <button class="btn btn-outline-danger" onclick="window.print()"><i class="fa-solid fa-file-pdf me-2"></i>Export PDF</button>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div>
                        <p class="text-muted mb-1">Penjualan</p>
                        <h3 class="mb-0"><?php echo format_rupiah($summary['total'] ?? 0); ?></h3>
                    </div>
                    <div class="stat-icon bg-primary"><i class="fa-solid fa-wallet"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div>
                        <p class="text-muted mb-1">Produk terjual</p>
                        <h3 class="mb-0"><?php echo (int) ($summary['qty'] ?? 0); ?> pcs</h3>
                    </div>
                    <div class="stat-icon bg-success"><i class="fa-solid fa-box-open"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div>
                        <p class="text-muted mb-1">Laba kotor</p>
                        <h3 class="mb-0"><?php echo format_rupiah($summary['profit'] ?? 0); ?></h3>
                    </div>
                    <div class="stat-icon bg-warning"><i class="fa-solid fa-chart-line"></i></div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="card-header">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">Rentang</label>
                        <select name="range" class="form-select">
                            <option value="today" <?php echo $range === 'today' ? 'selected' : ''; ?>>Hari ini</option>
                            <option value="week" <?php echo $range === 'week' ? 'selected' : ''; ?>>Minggu ini</option>
                            <option value="month" <?php echo $range === 'month' ? 'selected' : ''; ?>>Bulan ini</option>
                            <option value="year" <?php echo $range === 'year' ? 'selected' : ''; ?>>Tahun ini</option>
                            <option value="custom" <?php echo $range === 'custom' ? 'selected' : ''; ?>>Custom</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Dari</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo safe_output($dateFrom); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sampai</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo safe_output($dateTo); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
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
                                <th>Penjualan</th>
                                <th>Laba</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($transactions && $transactions->num_rows > 0): ?>
                                <?php while ($row = $transactions->fetch_assoc()): ?>
                                    <?php
                                        $profit = (float) $conn->query("SELECT COALESCE(SUM(profit), 0) AS total FROM transaction_details WHERE transaction_id = " . (int) $row['id'])->fetch_assoc()['total'];
                                    ?>
                                    <tr>
                                        <td><?php echo safe_output($row['transaction_number']); ?></td>
                                        <td><?php echo format_date_time($row['created_at']); ?></td>
                                        <td><?php echo safe_output($conn->query('SELECT full_name FROM users WHERE id = ' . (int) $row['user_id'])->fetch_assoc()['full_name'] ?? 'Unknown'); ?></td>
                                        <td><?php echo format_rupiah($row['total']); ?></td>
                                        <td><?php echo format_rupiah($profit); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Tidak ada data laporan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
