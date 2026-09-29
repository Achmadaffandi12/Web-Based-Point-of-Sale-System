<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Produk';
$conn = db_connect();

$search = trim($_GET['search'] ?? '');
$categoryFilter = (int) ($_GET['category'] ?? 0);
$statusFilter = $_GET['status'] ?? '';

$sql = 'SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id WHERE 1=1';
$params = [];
$types = '';

if ($search !== '') {
    $sql .= ' AND (p.code LIKE ? OR p.name LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

if ($categoryFilter > 0) {
    $sql .= ' AND p.category_id = ?';
    $params[] = $categoryFilter;
    $types .= 'i';
}

if ($statusFilter !== '') {
    $sql .= ' AND p.status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}

$sql .= ' ORDER BY p.id DESC';
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();

$categoriesResult = $conn->query('SELECT * FROM categories ORDER BY name ASC');
$categories = $categoriesResult ? $categoriesResult->fetch_all(MYSQLI_ASSOC) : [];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="main-panel">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h4 class="mb-1">Data Produk</h4>
                <p class="text-muted mb-0">Kelola daftar produk toko.</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                <i class="fa-solid fa-plus me-2"></i>Tambah Produk
            </button>
        </div>

        <div class="table-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">Daftar Produk</h5>
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-auto">
                        <input type="text" name="search" class="form-control" placeholder="Cari produk..." value="<?php echo safe_output($search); ?>">
                    </div>
                    <div class="col-auto">
                        <select name="category" class="form-select">
                            <option value="">Semua kategori</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo (int) $category['id']; ?>" <?php echo ($categoryFilter === (int) $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo safe_output($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="status" class="form-select">
                            <option value="">Semua status</option>
                            <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Nonaktif</option>
                            <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-outline-primary">Filter</button>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table datatable mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Kategori</th>
                                <th>HB</th>
                                <th>HJ</th>
                                <th>Stok</th>
                                <th>Satuan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($products && $products->num_rows > 0): ?>
                                <?php while ($product = $products->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo (int) $product['id']; ?></td>
                                        <td><?php echo safe_output($product['code']); ?></td>
                                        <td><?php echo safe_output($product['name']); ?></td>
                                        <td><?php echo safe_output($product['category_name']); ?></td>
                                        <td><?php echo format_rupiah($product['buy_price']); ?></td>
                                        <td><?php echo format_rupiah($product['sell_price']); ?></td>
                                        <td><?php echo (int) $product['stock']; ?></td>
                                        <td><?php echo safe_output($product['unit']); ?></td>
                                        <td><?php echo $product['status']; ?></td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-outline-primary edit-product" data-id="<?php echo (int) $product['id']; ?>" data-code="<?php echo safe_output($product['code']); ?>" data-name="<?php echo safe_output($product['name']); ?>" data-category_id="<?php echo (int) $product['category_id']; ?>" data-buy_price="<?php echo (float) $product['buy_price']; ?>" data-sell_price="<?php echo (float) $product['sell_price']; ?>" data-stock="<?php echo (int) $product['stock']; ?>" data-unit="<?php echo safe_output($product['unit']); ?>" data-status="<?php echo safe_output($product['status']); ?>" data-description="<?php echo safe_output($product['description'] ?? ''); ?>"><i class="fa-solid fa-pen"></i></button>
                                                <a href="proses/produk.php?action=delete&id=<?php echo (int) $product['id']; ?>" class="btn btn-sm btn-outline-danger" data-confirm="Apakah Anda yakin ingin menghapus produk ini?"><i class="fa-solid fa-trash"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-muted">Tidak ada produk yang sesuai.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="proses/produk.php" novalidate>
                <input type="hidden" name="action" id="productAction" value="create">
                <input type="hidden" name="id" id="productId" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalTitle">Tambah Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Kode Produk</label>
                            <input type="text" class="form-control" name="code" id="productCode" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Produk</label>
                            <input type="text" class="form-control" name="name" id="productName" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <select name="category_id" id="productCategory" class="form-select" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo (int) $category['id']; ?>"><?php echo safe_output($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Satuan</label>
                            <input type="text" class="form-control" name="unit" id="productUnit" value="pcs">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Harga Beli</label>
                            <input type="number" min="0" step="0.01" class="form-control" name="buy_price" id="productBuyPrice" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Harga Jual</label>
                            <input type="number" min="0" step="0.01" class="form-control" name="sell_price" id="productSellPrice" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stok</label>
                            <input type="number" min="0" class="form-control" name="stock" id="productStock" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" id="productStatus" class="form-select">
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                                <option value="draft">Draft</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" id="productDescription" rows="3"></textarea>
                        </div>
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
<script>
const productModal = document.getElementById('productModal');
const productModalTitle = document.getElementById('productModalTitle');
const productAction = document.getElementById('productAction');
const productId = document.getElementById('productId');
const productCode = document.getElementById('productCode');
const productName = document.getElementById('productName');
const productCategory = document.getElementById('productCategory');
const productBuyPrice = document.getElementById('productBuyPrice');
const productSellPrice = document.getElementById('productSellPrice');
const productStock = document.getElementById('productStock');
const productUnit = document.getElementById('productUnit');
const productStatus = document.getElementById('productStatus');
const productDescription = document.getElementById('productDescription');

const openProductModal = (action, data = {}) => {
    productAction.value = action;
    productId.value = data.id || '';
    productCode.value = data.code || '';
    productName.value = data.name || '';
    productCategory.value = data.category_id || '';
    productBuyPrice.value = data.buy_price || '';
    productSellPrice.value = data.sell_price || '';
    productStock.value = data.stock || 0;
    productUnit.value = data.unit || 'pcs';
    productStatus.value = data.status || 'active';
    productDescription.value = data.description || '';
    productModalTitle.textContent = action === 'update' ? 'Edit Produk' : 'Tambah Produk';
    new bootstrap.Modal(productModal).show();
};

document.querySelectorAll('.edit-product').forEach((button) => {
    button.addEventListener('click', function () {
        openProductModal('update', {
            id: button.dataset.id,
            code: button.dataset.code,
            name: button.dataset.name,
            category_id: button.dataset.category_id,
            buy_price: button.dataset.buy_price,
            sell_price: button.dataset.sell_price,
            stock: button.dataset.stock,
            unit: button.dataset.unit,
            status: button.dataset.status,
            description: button.dataset.description,
        });
    });
});
</script>
