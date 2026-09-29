<?php
require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo safe_output($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-card card">
        <div class="row g-0">
            <div class="col-lg-5 login-visual">
                <div class="login-visual-content">
                    <div class="brand-badge"><i class="fa-solid fa-store"></i></div>
                    <h2 class="fw-bold mb-3">POS Modern</h2>
                    <p class="fs-6 mb-4">Kelola transaksi, stok, dan laporan penjualan dengan cepat dan nyaman.</p>
                    <ul class="list-unstyled mt-4">
                        <li class="mb-3"><i class="fa-solid fa-circle-check me-2"></i> Dashboard realtime</li>
                        <li class="mb-3"><i class="fa-solid fa-circle-check me-2"></i> Manajemen produk & kategori</li>
                        <li class="mb-3"><i class="fa-solid fa-circle-check me-2"></i> Kasir dan laporan otomatis</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-7 login-form">
                <div class="mb-4">
                    <p class="text-uppercase text-primary fw-bold mb-2">Selamat datang</p>
                    <h3 class="fw-bold mb-1">Masuk ke akun Anda</h3>
                    <p class="text-muted mb-0">Silakan login untuk mengakses admin panel</p>
                </div>

                <?php if (isset($_SESSION['login_error'])): ?>
                    <div class="alert alert-danger" role="alert"><?php echo safe_output($_SESSION['login_error']); unset($_SESSION['login_error']); ?></div>
                <?php endif; ?>

                <form method="POST" action="index.php" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Show password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                            <label class="form-check-label" for="remember_me">Ingat saya</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Masuk
                    </button>

                    <div class="text-center text-muted small">
                        Demo login: username <strong>admin</strong> / password <strong>password</strong>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.12.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $_SESSION['login_error'] = 'Username dan password wajib diisi.';
        redirect('index.php');
    }

    try {
        $conn = db_connect();
        $stmt = $conn->prepare('SELECT id, username, password, full_name, role FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            if (!empty($_POST['remember_me'])) {
                $expire = time() + (60 * 60 * 24 * 30);
                setcookie('remember_user', $username, $expire, '/');
            }

            redirect('dashboard.php');
        }

        $_SESSION['login_error'] = 'Username atau password salah.';
        redirect('index.php');
    } catch (Throwable $e) {
        $_SESSION['login_error'] = db_connection_message();
        redirect('index.php');
    }
}
