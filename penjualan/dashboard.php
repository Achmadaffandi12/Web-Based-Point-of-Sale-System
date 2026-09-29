<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Dashboard';
$stats = get_stats();

$conn = db_connect();
$dailySales = $conn->query("SELECT DATE(created_at) AS sale_date, SUM(total) AS total FROM transactions WHERE status = 'completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at) ORDER BY sale_date ASC");
$dailyLabels = [];
$dailyValues = [];
while ($row = $dailySales->fetch_assoc()) {
    $dailyLabels[] = date('d M', strtotime($row['sale_date']));
    $dailyValues[] = (float) $row['total'];
}

$monthlySales = $conn->query("SELECT DATE_FORMAT(created_at, '%b %Y') AS month_label, SUM(total) AS total FROM transactions WHERE status = 'completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY created_at ASC");
$monthlyLabels = [];
$monthlyValues = [];
while ($row = $monthlySales->fetch_assoc()) {
    $monthlyLabels[] = $row['month_label'];
    $monthlyValues[] = (float) $row['total'];
}

$bestProducts = $conn->query("SELECT p.name, SUM(td.quantity) AS qty FROM transaction_details td JOIN products p ON p.id = td.product_id GROUP BY td.product_id ORDER BY qty DESC LIMIT 5");
$recentTransactions = $conn->query("SELECT t.*, u.full_name AS cashier FROM transactions t JOIN users u ON u.id = t.user_id ORDER BY t.created_at DESC LIMIT 5");
$lowStock = $conn->query("SELECT * FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 5");

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="main-panel">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>
    <div class="content-wrapper">
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div>
                        <p class="text-muted mb-1">Total Produk</p>
                        <h3 class="mb-0"><?php echo $stats['products']; ?></h3>
                    </div>
                    <div class="stat-icon bg-primary"><i class="fa-solid fa-box-open"></i></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div>
                        <p class="text-muted mb-1">Total Kategori</p>
                        <h3 class="mb-0"><?php echo $stats['categories']; ?></h3>
                    </div>
                    <div class="stat-icon bg-info"><i class="fa-solid fa-tags"></i></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div>
                        <p class="text-muted mb-1">Total Transaksi</p>
                        <h3 class="mb-0"><?php echo $stats['transactions']; ?></h3>
                    </div>
                    <div class="stat-icon bg-success"><i class="fa-solid fa-cash-register"></i></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div>
                        <p class="text-muted mb-1">Total Pendapatan</p>
                        <h3 class="mb-0"><?php echo format_rupiah($stats['revenue']); ?></h3>
                    </div>
                    <div class="stat-icon bg-warning"><i class="fa-solid fa-wallet"></i></div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="stat-card">
                    <div>
                        <p class="text-muted mb-1">Produk terjual hari ini</p>
                        <h3 class="mb-0"><?php echo $stats['sold_today']; ?></h3>
                    </div>
                    <div class="stat-icon bg-success"><i class="fa-solid fa-chart-column"></i></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <div>
                        <p class="text-muted mb-1">Produk stok menipis</p>
                        <h3 class="mb-0"><?php echo $stats['low_stock']; ?></h3>
                    </div>
                    <div class="stat-icon bg-danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-8">
                <div class="chart-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Grafik penjualan harian</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="dailySalesChart" height="120"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="chart-card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Produk paling banyak terjual</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($bestProducts && $bestProducts->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($product = $bestProducts->fetch_assoc()): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span><?php echo safe_output($product['name']); ?></span>
                                        <span class="badge bg-primary-subtle text-primary"><?php echo (int) $product['qty']; ?> pcs</span>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">Belum ada data penjualan.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-8">
                <div class="chart-card">
                    <div class="card-header">
                        <h5 class="mb-0">Grafik penjualan bulanan</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlySalesChart" height="110"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="chart-card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Notifikasi stok menipis</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($lowStock && $lowStock->num_rows > 0): ?>
                            <ul class="list-group list-group-flush">
                                <?php while ($item = $lowStock->fetch_assoc()): ?>
                                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                        <span><?php echo safe_output($item['name']); ?></span>
                                        <span class="badge bg-warning-subtle text-warning"><?php echo (int) $item['stock']; ?> stok</span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <div class="empty-state">Semua stok aman.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-7">
                <div class="table-card">
                    <div class="card-header">
                        <h5 class="mb-0">Transaksi terbaru</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>No. Transaksi</th>
                                        <th>Kasir</th>
                                        <th>Total</th>
                                        <th>Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recentTransactions && $recentTransactions->num_rows > 0): ?>
                                        <?php while ($trx = $recentTransactions->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo safe_output($trx['transaction_number']); ?></td>
                                                <td><?php echo safe_output($trx['cashier']); ?></td>
                                                <td><?php echo format_rupiah($trx['total']); ?></td>
                                                <td><?php echo format_date_time($trx['created_at']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Belum ada transaksi.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="table-card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Ringkasan hari ini</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Penjualan hari ini</span>
                            <strong><?php echo format_rupiah($stats['revenue']); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Produk terjual</span>
                            <strong><?php echo $stats['sold_today']; ?> pcs</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Stok menipis</span>
                            <strong><?php echo $stats['low_stock']; ?> item</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Transaksi</span>
                            <strong><?php echo $stats['transactions']; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script>
const dailyLabels = <?php echo json_encode($dailyLabels); ?>;
const dailyValues = <?php echo json_encode($dailyValues); ?>;
const monthlyLabels = <?php echo json_encode($monthlyLabels); ?>;
const monthlyValues = <?php echo json_encode($monthlyValues); ?>;

new Chart(document.getElementById('dailySalesChart'), {
    type: 'line',
    data: {
        labels: dailyLabels.length ? dailyLabels : ['Tidak ada'],
        datasets: [{
            label: 'Penjualan',
            data: dailyValues.length ? dailyValues : [0],
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37,99,235,0.15)',
            fill: true,
            tension: 0.4,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

new Chart(document.getElementById('monthlySalesChart'), {
    type: 'bar',
    data: {
        labels: monthlyLabels.length ? monthlyLabels : ['Tidak ada'],
        datasets: [{
            label: 'Pendapatan',
            data: monthlyValues.length ? monthlyValues : [0],
            backgroundColor: '#0f172a',
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
