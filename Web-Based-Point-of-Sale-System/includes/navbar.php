<?php
$currentUser = current_user();
?>
<header class="topbar">
    <div class="d-flex align-items-center justify-content-between w-100 gap-3">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light d-lg-none" id="sidebarToggle" type="button">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div>
                <h4 class="page-title mb-0"><?php echo isset($pageTitle) ? safe_output($pageTitle) : 'Dashboard'; ?></h4>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="topbar-search d-none d-md-flex">
                <i class="fa-solid fa-search"></i>
                <input type="text" placeholder="Cari cepat..." class="form-control border-0 shadow-none">
            </div>
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                    <span class="avatar-circle"><?php echo strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 1)); ?></span>
                    <span><?php echo safe_output($currentUser['full_name'] ?? 'User'); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#"><i class="fa-solid fa-user me-2"></i>Profil</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fa-solid fa-gear me-2"></i>Pengaturan</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>
