<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/config/db.php';

$errors = [];
$email = '';

if (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $stmt = $mysqli->prepare("SELECT 1 FROM profiles WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stmt->store_result();
    $hasProfile = $stmt->num_rows > 0;
    $stmt->close();

    header('Location: ' . ($hasProfile ? 'dashboard.php' : 'edit_profile.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email non valida.";
    }
    if ($password === '') {
        $errors[] = "Inserisci la password.";
    }

    if (!$errors) {
        $stmt = $mysqli->prepare("SELECT id, email, password_hash FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($id, $emailDb, $passwordHashDb);

        $found = $stmt->fetch();
        $stmt->close();

        if (!$found || !password_verify($password, (string)$passwordHashDb)) {
            $errors[] = "Credenziali non valide.";
        } else {
            $_SESSION['user_id'] = (int)$id;
            $_SESSION['user_email'] = (string)$emailDb;

            session_regenerate_id(true);

            $stmt = $mysqli->prepare("SELECT 1 FROM profiles WHERE user_id = ? LIMIT 1");
            $userId = (int)$id;
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->store_result();
            $hasProfile = $stmt->num_rows > 0;
            $stmt->close();

            header('Location: ' . ($hasProfile ? 'dashboard.php' : 'edit_profile.php'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="description" content="Accedi al portale UniBoQuest per gestire le tue missioni." />

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Share+Tech+Mono&display=swap" rel="stylesheet" />

        <link rel="stylesheet" href="css/main.css">

        <title>UniBoQuest - Login</title>
    </head>

    <body class="manual-bg auth-page">
        <a href="#contenuto" class="skip-link">Salta al contenuto principale</a>

        <header class="header-glass">
            <nav class="navbar navbar-expand-md navbar-dark">
                <div class="container-fluid">
                    <a class="navbar-brand font-8bit" href="index.html">UniBoQuest</a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Apri menu">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="nav">
                        <ul class="navbar-nav mx-auto mb-2 mb-md-0 ubq-nav-center">
                            <li class="nav-item"><a class="nav-link" href="index.html">HOME</a></li>
                            <li class="nav-item"><a class="nav-link" href="gioco.html">IL GIOCO</a></li>
                            <li class="nav-item"><a class="nav-link" href="faq.html">FAQ</a></li>
                        </ul>
                        <div class="d-flex gap-2 ubq-nav-right">
                            <a class="btn-pixel-yellow" href="login.php" aria-current="page">Accedi</a>
                            <a class="btn-pixel" href="registrazione.php">Registrati</a>
                        </div>
                    </div>
                </div>
            </nav>
        </header>

        <main id="contenuto" class="container">
            <div class="auth-card">
                <h2 class="mb-4 font-8bit text-center text-white">LOGIN</h2>

                <?php if (!empty($errors)): ?>
                    <div class="checkin-msg err" style="margin-bottom: 1rem;">
                        <?php foreach ($errors as $e): ?>
                            <div><?php echo htmlspecialchars($e); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Ateneo</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control p-3"
                            placeholder="nome.cognome@studio.unibo.it"
                            autocomplete="email"
                            required
                            value="<?php echo htmlspecialchars($email); ?>"
                        >
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control p-3"
                            autocomplete="current-password"
                            required
                        >
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn-pixel-yellow py-3 font-8bit" style="font-size: 0.9rem;">
                            ENTRA
                        </button>
                    </div>
                </form>

                <div class="auth-footer-link">
                    Non hai ancora un account? <a href="registrazione.php">Registrati ora</a>
                </div>
            </div>
        </main>

        <footer class="footer-ubq mt-5">
            <div class="container py-4">
                <div class="row gy-3 align-items-start">
                    <div class="col-md-4">
                        <h5 class="fw-bold mb-2 text-white">UniBoQuest</h5>
                        <p class="mb-1 small text-white opacity-75">Il gioco che trasforma la vita universitaria in una quest.</p>
                        <p class="small mb-0 text-white opacity-50">Progetto didattico – Università di Cesena.</p>
                    </div>
                    <div class="col-md-3">
                        <h6 class="fw-bold mb-2 text-white">Navigazione</h6>
                        <ul class="list-unstyled small mb-0">
                            <li><a href="gioco.html" class="footer-link text-white text-decoration-none">Il Gioco</a></li>
                            <li><a href="faq.html" class="footer-link text-white text-decoration-none">FAQ</a></li>
                        </ul>
                    </div>
                    <div class="col-md-3">
                        <h6 class="fw-bold mb-2 text-white">Seguici</h6>
                        <div class="footer-social d-flex gap-3">
                            <a href="#" class="text-white fs-5" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                            <a href="#" class="text-white fs-5" aria-label="Discord"><i class="bi bi-discord"></i></a>
                            <a href="https://github.com/LolloMarche25/uniboquest.git" class="text-white fs-5" aria-label="GitHub"><i class="bi bi-github"></i></a>
                        </div>
                    </div>
                </div>
                 <div class="footer-bottom border-top border-light-subtle mt-4 pt-3 d-flex flex-column flex-md-row justify-content-between align-items-center small text-secondary">
                    <span>&copy; 2026 UniBoQuest – Prototipo Alpha.</span>
                </div>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
