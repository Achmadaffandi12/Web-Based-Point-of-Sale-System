<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Kasir';
$conn = db_connect();
$categories = $conn->query('SELECT * FROM categories ORDER BY name ASC');
$products = $conn->query("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id WHERE p.status = 'active' ORDER BY p.name ASC");
$productsJson = [];
while ($product = $products->fetch_assoc()) {
    $productsJson[] = [
        'id' => (int) $product['id'],
        'code' => $product['code'],
        'name' => $product['name'],
        'category_id' => (int) $product['category_id'],
        'category_name' => $product['category_name'],
        'sell_price' => (float) $product['sell_price'],
        'stock' => (int) $product['stock'],
    ];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="main-panel">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>
    <div class="content-wrapper">
        <div class="pos-layout">
            <div class="pos-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h4 class="mb-1">Kasir</h4>
                        <p class="text-muted mb-0">Transaksi penjualan</p>
                    </div>
                    <div class="d-flex gap-2">
                        <input type="text" id="productSearch" class="form-control" placeholder="Cari produk...">
                        <select id="categoryFilter" class="form-select">
                            <option value="all">Semua</option>
                            <?php while ($category = $categories->fetch_assoc()): ?>
                                <option value="<?php echo (int) $category['id']; ?>"><?php echo safe_output($category['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div id="productList" class="product-grid"></div>
            </div>

            <div class="pos-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Keranjang</h5>
                    <button class="btn btn-sm btn-outline-danger" id="clearCartBtn">Kosongkan</button>
                </div>

                <div id="cartItems" class="mb-3">
                    <div class="empty-state">
                        <i class="fa-solid fa-cart-shopping fs-1 d-block mb-2"></i>
                        Keranjang masih kosong.
                    </div>
                </div>

                <div class="receipt-box mb-3">
                    <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><strong id="subtotalValue">Rp 0</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Diskon</span><input type="number" id="discountInput" class="form-control form-control-sm w-25 text-end" value="0" min="0"></div>
                    <div class="d-flex justify-content-between mb-2"><span>Pajak</span><input type="number" id="taxInput" class="form-control form-control-sm w-25 text-end" value="0" min="0"></div>
                    <div class="d-flex justify-content-between mb-2"><span>Total</span><strong id="totalValue">Rp 0</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Bayar</span><input type="number" id="paymentInput" class="form-control form-control-sm w-50 text-end" value="0" min="0"></div>
                    <div class="d-flex justify-content-between"><span>Kembalian</span><strong id="changeValue">Rp 0</strong></div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Metode Pembayaran</label>
                    <select id="paymentMethod" class="form-select">
                        <option value="cash">Cash</option>
                        <option value="transfer">Transfer</option>
                        <option value="qris">QRIS</option>
                        <option value="e-wallet">E-Wallet</option>
                    </select>
                </div>

                <button class="btn btn-primary w-100" id="checkoutBtn">Bayar</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script>
const products = <?php echo json_encode($productsJson); ?>;
let cart = [];

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}

function getProductById(id) {
    return products.find(p => p.id === Number(id));
}

function renderProducts() {
    const search = document.getElementById('productSearch').value.toLowerCase();
    const category = document.getElementById('categoryFilter').value;
    const filtered = products.filter(product => {
        const matchSearch = product.name.toLowerCase().includes(search) || product.code.toLowerCase().includes(search);
        const matchCategory = category === 'all' || String(product.category_id) === String(category);
        return matchSearch && matchCategory;
    });

    const productList = document.getElementById('productList');
    if (!filtered.length) {
        productList.innerHTML = '<div class="empty-state">Produk tidak ditemukan.</div>';
        return;
    }

    productList.innerHTML = filtered.map(product => `
        <div class="product-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-primary-subtle text-primary">${product.code}</span>
                <span class="badge bg-success-subtle text-success">${product.stock} stok</span>
            </div>
            <h6 class="mb-1">${product.name}</h6>
            <p class="text-muted mb-2">${product.category_name}</p>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <strong>${formatRupiah(product.sell_price)}</strong>
            </div>
            <button class="btn btn-primary w-100 add-to-cart" data-id="${product.id}">Tambah</button>
        </div>
    `).join('');

    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function () {
            addToCart(Number(this.dataset.id));
        });
    });
}

function addToCart(productId) {
    const product = getProductById(productId);
    if (!product) return;

    const existing = cart.find(item => item.id === productId);
    if (existing) {
        if (existing.quantity >= product.stock) {
            Swal.fire({ icon: 'warning', title: 'Stok tidak cukup', text: 'Jumlah produk melebihi stok yang tersedia.' });
            return;
        }
        existing.quantity += 1;
        existing.subtotal = existing.quantity * product.sell_price;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: product.sell_price,
            quantity: 1,
            subtotal: product.sell_price,
            stock: product.stock,
        });
    }
    renderCart();
}

function renderCart() {
    const cartItems = document.getElementById('cartItems');
    if (!cart.length) {
        cartItems.innerHTML = '<div class="empty-state"><i class="fa-solid fa-cart-shopping fs-1 d-block mb-2"></i>Keranjang masih kosong.</div>';
        updateTotals();
        return;
    }

    cartItems.innerHTML = cart.map(item => `
        <div class="cart-item">
            <div>
                <strong>${item.name}</strong><br>
                <small>${formatRupiah(item.price)}</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="qty-box">
                    <button type="button" class="decrease-qty" data-id="${item.id}">-</button>
                    <span>${item.quantity}</span>
                    <button type="button" class="increase-qty" data-id="${item.id}">+</button>
                </div>
                <strong>${formatRupiah(item.subtotal)}</strong>
                <button type="button" class="btn btn-sm btn-link text-danger remove-item" data-id="${item.id}"><i class="fa-solid fa-trash"></i></button>
            </div>
        </div>
    `).join('');

    document.querySelectorAll('.increase-qty').forEach(button => {
        button.addEventListener('click', function () {
            const item = cart.find(i => i.id === Number(this.dataset.id));
            const product = getProductById(Number(this.dataset.id));
            if (item && product && item.quantity < product.stock) {
                item.quantity += 1;
                item.subtotal = item.quantity * item.price;
                renderCart();
            } else {
                Swal.fire({ icon: 'warning', title: 'Stok habis', text: 'Stok produk sudah mencapai batas.' });
            }
        });
    });

    document.querySelectorAll('.decrease-qty').forEach(button => {
        button.addEventListener('click', function () {
            const item = cart.find(i => i.id === Number(this.dataset.id));
            if (!item) return;
            item.quantity -= 1;
            if (item.quantity <= 0) {
                cart = cart.filter(i => i.id !== Number(this.dataset.id));
            } else {
                item.subtotal = item.quantity * item.price;
            }
            renderCart();
        });
    });

    document.querySelectorAll('.remove-item').forEach(button => {
        button.addEventListener('click', function () {
            cart = cart.filter(i => i.id !== Number(this.dataset.id));
            renderCart();
        });
    });

    updateTotals();
}

function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
    const discount = Number(document.getElementById('discountInput').value || 0);
    const tax = Number(document.getElementById('taxInput').value || 0);
    const total = subtotal - discount + tax;
    const payment = Number(document.getElementById('paymentInput').value || 0);
    const change = payment - total;

    document.getElementById('subtotalValue').textContent = formatRupiah(subtotal);
    document.getElementById('totalValue').textContent = formatRupiah(total < 0 ? 0 : total);
    document.getElementById('changeValue').textContent = formatRupiah(change > 0 ? change : 0);
}

document.getElementById('productSearch').addEventListener('input', renderProducts);
document.getElementById('categoryFilter').addEventListener('change', renderProducts);
document.getElementById('discountInput').addEventListener('input', updateTotals);
document.getElementById('taxInput').addEventListener('input', updateTotals);
document.getElementById('paymentInput').addEventListener('input', updateTotals);
document.getElementById('clearCartBtn').addEventListener('click', function () {
    cart = [];
    renderCart();
});

document.getElementById('checkoutBtn').addEventListener('click', function () {
    if (!cart.length) {
        Swal.fire({ icon: 'warning', title: 'Keranjang kosong', text: 'Tambahkan produk sebelum checkout.' });
        return;
    }

    const total = Number((cart.reduce((sum, item) => sum + item.subtotal, 0) - Number(document.getElementById('discountInput').value || 0) + Number(document.getElementById('taxInput').value || 0)).toFixed(2));
    const payment = Number(document.getElementById('paymentInput').value || 0);
    if (payment < total) {
        Swal.fire({ icon: 'warning', title: 'Pembayaran kurang', text: 'Jumlah pembayaran harus lebih besar atau sama dengan total transaksi.' });
        return;
    }

    const payload = {
        cart,
        discount: Number(document.getElementById('discountInput').value || 0),
        tax: Number(document.getElementById('taxInput').value || 0),
        payment_method: document.getElementById('paymentMethod').value,
        payment_amount: payment,
    };

    fetch('proses/transaksi.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    }).then(response => response.json()).then(result => {
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Transaksi berhasil',
                text: 'Nomor transaksi: ' + result.transaction_number,
                confirmButtonText: 'Cetak struk'
            }).then((modalResult) => {
                if (modalResult.isConfirmed) {
                    window.open('receipt.php?id=' + result.transaction_id, '_blank');
                }
                window.location.href = 'riwayat.php';
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: result.message || 'Transaksi gagal diproses.' });
        }
    }).catch(() => {
        Swal.fire({ icon: 'error', title: 'Gagal', text: 'Ada masalah saat memproses transaksi.' });
    });
});

renderProducts();
renderCart();
</script>
