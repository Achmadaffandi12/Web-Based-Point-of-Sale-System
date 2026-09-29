<?php
$menu = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php', 'icon' => 'fa-house'],
    ['title' => 'Produk', 'url' => 'produk.php', 'icon' => 'fa-box-open'],
    ['title' => 'Kategori', 'url' => 'kategori.php', 'icon' => 'fa-tags'],
    ['title' => 'Transaksi', 'url' => 'transaksi.php', 'icon' => 'fa-cash-register'],
    ['title' => 'Riwayat', 'url' => 'riwayat.php', 'icon' => 'fa-clock-rotate-left'],
    ['title' => 'Stok', 'url' => 'stok.php', 'icon' => 'fa-warehouse'],
    ['title' => 'Laporan', 'url' => 'laporan.php', 'icon' => 'fa-chart-line'],
    ['title' => 'Pengguna', 'url' => '#', 'icon' => 'fa-user'],
    ['title' => 'Pengaturan', 'url' => '#', 'icon' => 'fa-gear'],
];
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="brand-wrap">
            <div class="brand-icon"><i class="fa-solid fa-store"></i></div>
            <div class="brand-text">
                <span class="brand-name">POS Modern</span>
                <small>Retail Suite</small>
            </div>
        </div>
    </div>

    <ul class="nav flex-column">
        <?php foreach ($menu as $item): ?>
            <?php $active = $currentPage === basename($item['url']) ? 'active' : ''; ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $active; ?>" href="<?php echo safe_output($item['url']); ?>">
                    <i class="fa-solid <?php echo safe_output($item['icon']); ?>"></i>
                    <span><?php echo safe_output($item['title']); ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
