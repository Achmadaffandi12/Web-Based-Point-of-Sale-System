<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Kategori';
$conn = db_connect();
$search = trim($_GET['search'] ?? '');
$sql = 'SELECT c.*, COUNT(p.id) AS total_produk FROM categories c LEFT JOIN products p ON p.category_id = c.id WHERE c.name LIKE ? GROUP BY c.id ORDER BY c.id DESC';
$stmt = $conn->prepare($sql);
$like = '%' . $search . '%';
$stmt->bind_param('s', $like);
$stmt->execute();
$categories = $stmt->get_result();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="main-panel">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h4 class="mb-1">Manajemen Kategori</h4>
                <p class="text-muted mb-0">Kelola kategori produk toko Anda</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                <i class="fa-solid fa-plus me-2"></i>Tambah Kategori
            </button>
        </div>

        <div class="table-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">Daftar Kategori</h5>
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <input type="text" name="search" class="form-control" placeholder="Cari kategori..." value="<?php echo safe_output($search); ?>">
                    <button type="submit" class="btn btn-outline-primary"><i class="fa-solid fa-search"></i></button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table datatable mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Kategori</th>
                                <th>Jumlah Produk</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($categories && $categories->num_rows > 0): ?>
                                <?php while ($category = $categories->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo (int) $category['id']; ?></td>
                                        <td><?php echo safe_output($category['name']); ?></td>
                                        <td><?php echo (int) $category['total_produk']; ?></td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-outline-primary edit-category" data-id="<?php echo (int) $category['id']; ?>" data-name="<?php echo safe_output($category['name']); ?>">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                <a href="proses/kategori.php?action=delete&id=<?php echo (int) $category['id']; ?>" class="btn btn-sm btn-outline-danger" data-confirm="Apakah Anda yakin ingin menghapus kategori ini?">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Belum ada kategori.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="proses/kategori.php">
                <input type="hidden" name="action" id="categoryAction" value="create">
                <input type="hidden" name="id" id="categoryId" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalTitle">Tambah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori</label>
                        <input type="text" class="form-control" name="name" id="categoryName" required>
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
const categoryModal = document.getElementById('categoryModal');
const categoryModalTitle = document.getElementById('categoryModalTitle');
const categoryAction = document.getElementById('categoryAction');
const categoryId = document.getElementById('categoryId');
const categoryName = document.getElementById('categoryName');

const openCategoryModal = (action, id = '', name = '') => {
    categoryAction.value = action;
    categoryId.value = id;
    categoryName.value = name;
    categoryModalTitle.textContent = action === 'update' ? 'Edit Kategori' : 'Tambah Kategori';
    const modal = new bootstrap.Modal(categoryModal);
    modal.show();
};

document.querySelectorAll('.edit-category').forEach((button) => {
    button.addEventListener('click', function () {
        openCategoryModal('update', button.dataset.id, button.dataset.name);
    });
});
</script>
