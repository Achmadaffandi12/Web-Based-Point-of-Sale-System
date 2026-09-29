<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Stok';
$conn = db_connect();

$search = trim($_GET['search'] ?? '');
$sql = 'SELECT sm.*, p.name AS product_name, u.full_name AS user_name FROM stock_movements sm JOIN products p ON p.id = sm.product_id JOIN users u ON u.id = sm.user_id WHERE p.name LIKE ? ORDER BY sm.created_at DESC';
$stmt = $conn->prepare($sql);
$like = '%' . $search . '%';
$stmt->bind_param('s', $like);
$stmt->execute();
$movements = $stmt->get_result();

$products = $conn->query('SELECT * FROM products ORDER BY name ASC');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="main-panel">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h4 class="mb-1">Manajemen Stok</h4>
                <p class="text-muted mb-0">Pantau stok masuk, keluar, dan penyesuaian</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#stockModal">
                <i class="fa-solid fa-plus me-2"></i>Penyesuaian Stok
            </button>
        </div>

        <div class="table-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">Riwayat Perubahan Stok</h5>
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <input type="text" name="search" class="form-control" placeholder="Cari produk..." value="<?php echo safe_output($search); ?>">
                    <button type="submit" class="btn btn-outline-primary">Cari</button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table datatable mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Produk</th>
                                <th>Jenis</th>
                                <th>Jumlah</th>
                                <th>Stok Sebelum</th>
                                <th>Stok Sesudah</th>
                                <th>Keterangan</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($movements && $movements->num_rows > 0): ?>
                                <?php while ($movement = $movements->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo format_date_time($movement['created_at']); ?></td>
                                        <td><?php echo safe_output($movement['product_name']); ?></td>
                                        <td><span class="badge bg-primary-subtle text-primary"><?php echo safe_output($movement['movement_type']); ?></span></td>
                                        <td><?php echo (int) $movement['quantity']; ?></td>
                                        <td><?php echo (int) $movement['stock_before']; ?></td>
                                        <td><?php echo (int) $movement['stock_after']; ?></td>
                                        <td><?php echo safe_output($movement['description'] ?? '-'); ?></td>
                                        <td><?php echo safe_output($movement['user_name']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Belum ada riwayat stok.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="stockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="proses/stok.php">
                <div class="modal-header">
                    <h5 class="modal-title">Penyesuaian Stok</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Produk</label>
                        <select name="product_id" class="form-select" required>
                            <?php while ($product = $products->fetch_assoc()): ?>
                                <option value="<?php echo (int) $product['id']; ?>"><?php echo safe_output($product['name']); ?> (<?php echo (int) $product['stock']; ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis</label>
                        <select name="movement_type" class="form-select">
                            <option value="in">Stok Masuk</option>
                            <option value="out">Stok Keluar</option>
                            <option value="adjustment">Penyesuaian</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah</label>
                        <input type="number" name="quantity" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea name="description" rows="3" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
