<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/profile_guard.php';
require __DIR__ . '/config/db.php';

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $mid = trim((string)($_POST['mission_id'] ?? ''));

    if ($action === 'complete' && $mid !== '' && preg_match('/^[a-z0-9_-]{1,50}$/i', $mid)) {
        $stmt = $mysqli->prepare("SELECT requires_checkin FROM missions WHERE id = ? AND active = 1 LIMIT 1");
        $stmt->bind_param("s", $mid);
        $stmt->execute();
        $mrow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $requiresCheckin = $mrow ? ((int)$mrow['requires_checkin'] === 1) : false;

        if (!$requiresCheckin) {
            $stmt = $mysqli->prepare("
                UPDATE user_missions
                SET status='completed', completed_at = NOW()
                WHERE user_id = ? AND mission_id = ? AND status='active'
            ");
            $stmt->bind_param("is", $userId, $mid);
            $stmt->execute();
            $stmt->close();
        }
    }
    header('Location: dashboard.php');
    exit;
}

$stmt = $mysqli->prepare("SELECT nickname, display_name FROM profiles WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

$displayName = ($p && !empty($p['display_name'])) ? (string)$p['display_name'] :
               (($p && !empty($p['nickname'])) ? (string)$p['nickname'] :
               ((string)($_SESSION['user_email'] ?? 'Player')));

$XP_PER_LEVEL = 100;

$stmt = $mysqli->prepare("
    SELECT COALESCE(SUM(m.xp), 0) AS xp_total
    FROM user_missions um
    JOIN missions m ON m.id = um.mission_id
    WHERE um.user_id = ? AND um.status = 'completed'
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$xpTotal = (int)($stmt->get_result()->fetch_assoc()['xp_total'] ?? 0);
$stmt->close();

$stmt = $mysqli->prepare("
    SELECT COUNT(*) AS cnt
    FROM user_missions
    WHERE user_id = ? AND status = 'active'
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$activeCount = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
$stmt->close();

$stmt = $mysqli->prepare("
    SELECT m.id, m.title, m.requires_checkin
    FROM user_missions um
    JOIN missions m ON m.id = um.mission_id
    WHERE um.user_id = ? AND um.status = 'active'
    ORDER BY um.joined_at DESC
    LIMIT 3
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$activeMissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$level = intdiv($xpTotal, $XP_PER_LEVEL) + 1;
$inLevel = $xpTotal % $XP_PER_LEVEL;
$pct = (int)round(($inLevel / $XP_PER_LEVEL) * 100);
$pct = max(0, min(100, $pct));

$xpInLevelLabel = $inLevel . " / " . $XP_PER_LEVEL;
$xpTotalLabel = $xpTotal . " / " . ($level * $XP_PER_LEVEL);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Dashboard UniBoQuest (area privata)." />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Share+Tech+Mono&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/main.css" />

    <title>UniBoQuest - Dashboard</title>
</head>

<body class="manual-bg dashboard-page">
    <a href="#contenuto" class="skip-link">Salta al contenuto principale</a>

    <header class="header-glass">
        <nav class="navbar navbar-expand-md navbar-dark">
            <div class="container-fluid">
                <a class="navbar-brand font-8bit" href="dashboard.php" aria-label="UniBoQuest Dashboard">UniBoQuest</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"
                    aria-controls="nav" aria-expanded="false" aria-label="Apri menu">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="nav">
                    <ul class="navbar-nav mx-auto mb-2 mb-md-0 ubq-nav-center">
                        <li class="nav-item"><a class="nav-link active" href="dashboard.php" aria-current="page">DASHBOARD</a></li>
                        <li class="nav-item"><a class="nav-link" href="missioni.php">MISSIONI</a></li>
                        <li class="nav-item"><a class="nav-link" href="profilo.php">PROFILO</a></li>
                        <?php if (($_SESSION['user_role'] ?? 'user') === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="admin_missions.php">ADMIN</a></li>
                        <?php endif; ?>
                    </ul>
                    <div class="d-flex gap-2 ubq-nav-right">
                        <a class="btn-pixel-yellow" href="logout.php">Esci</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main id="contenuto" class="container mt-4">
        <div class="dashboard-card">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <h1 class="dashboard-title font-8bit h2" style="font-size: 1.15rem;">DASHBOARD</h1>
                    <p class="dashboard-subtitle mb-0">
                        Bentornato, <strong><?php echo htmlspecialchars($displayName); ?></strong>!
                    </p>
                </div>
                <span class="dashboard-badge">
                    LIV <?php echo (int)$level; ?> &bull; <?php echo (int)$xpTotal; ?> XP
                </span>
            </div>

            <hr class="my-4" style="border-color: rgba(255,255,255,.15);">

            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="dashboard-tile">
                        <div class="dashboard-tile-label">Livello</div>
                        <div class="dashboard-tile-value"><?php echo (int)$level; ?></div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="dashboard-tile">
                        <div class="dashboard-tile-label">XP</div>
                        <div class="dashboard-tile-value"><?php echo htmlspecialchars($xpInLevelLabel); ?></div>
                        <div class="dashboard-subtitle mb-0 small" style="margin-top: .35rem;">
                            Totale: <span><?php echo htmlspecialchars($xpTotalLabel); ?></span>
                        </div>
                        <div class="progress dashboard-progress mt-2" role="progressbar" aria-label="Progresso XP"
                            aria-valuenow="<?php echo (int)$pct; ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: <?php echo (int)$pct; ?>%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="dashboard-tile">
                        <div class="dashboard-tile-label">Missioni attive</div>
                        <div class="dashboard-tile-value"><?php echo (int)$activeCount; ?></div>
                    </div>
                </div>
            </div>

            <section class="mt-4 dashboard-tile">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h2 class="dashboard-tile-label h6 mb-1">Azioni rapide</h2>
                        <p class="dashboard-subtitle mb-0">Inizia da qui</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn-pixel" href="missioni.php">Vai alle missioni</a>
                        <a class="btn-pixel" href="checkin.php?id=checkin">Check-in</a>
                        <a class="btn-pixel-yellow" href="edit_profile.php">Modifica profilo</a>
                    </div>
                </div>
            </section>

            <section class="mt-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h2 class="font-8bit mb-0 h3" style="font-size: 0.95rem; color: #ffca2c; background-color: #000; padding: 5px 10px; border-radius: 4px; display: inline-block;">
                        MISSIONI CONSIGLIATE
                    </h2>
                    <a class="dashboard-badge text-decoration-none" href="missioni.php">VEDI TUTTE</a>
                </div>

                <div class="d-grid gap-2">
                    <div class="dashboard-mission">
                        <div>
                            <p class="dashboard-mission-title mb-1">Prima Quest: Benvenuto in UniBoQuest</p>
                            <div class="dashboard-mission-meta small">+20 XP &bull; Facile &bull; 5 min</div>
                        </div>
                        <a class="btn-pixel" href="missione_dettaglio.php?id=intro">Apri</a>
                    </div>
                    <div class="dashboard-mission">
                        <div>
                            <p class="dashboard-mission-title mb-1">Check-in Evento</p>
                            <div class="dashboard-mission-meta small">+50 XP &bull; Media &bull; QR / codice</div>
                        </div>
                        <a class="btn-pixel" href="missione_dettaglio.php?id=checkin">Apri</a>
                    </div>
                </div>
            </section>

            <section class="mt-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h2 class="font-8bit mb-0 h3" style="font-size: 0.95rem; color: #ffca2c; background-color: #000; padding: 5px 10px; border-radius: 4px; display: inline-block;">
                        LE TUE MISSIONI ATTIVE
                    </h2>
                    <a class="dashboard-badge text-decoration-none" href="missioni.php">GESTISCI</a>
                </div>

                <?php if (empty($activeMissions)): ?>
                    <div class="dashboard-mission" style="opacity: .9;">
                        <div>
                            <p class="dashboard-mission-title mb-1">Nessuna missione attiva</p>
                            <div class="dashboard-mission-meta small">Vai su Missioni e premi “Partecipa”.</div>
                        </div>
                        <a class="btn-pixel" href="missioni.php">Apri</a>
                    </div>
                <?php else: ?>
                    <div class="d-grid gap-2">
                        <?php foreach ($activeMissions as $m): ?>
                            <div class="dashboard-mission">
                                <div>
                                    <p class="dashboard-mission-title mb-1"><?php echo htmlspecialchars((string)$m['title']); ?></p>
                                    <div class="dashboard-mission-meta small">Stato: In corso</div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn-pixel" href="missione_dettaglio.php?id=<?php echo urlencode((string)$m['id']); ?>">Apri</a>
                                    <?php if ((int)($m['requires_checkin'] ?? 0) === 1): ?>
                                        <a class="btn-pixel-yellow" href="checkin.php?id=<?php echo urlencode((string)$m['id']); ?>">Check-in</a>
                                    <?php else: ?>
                                        <form method="post" action="dashboard.php" style="display:inline;">
                                            <input type="hidden" name="action" value="complete">
                                            <input type="hidden" name="mission_id" value="<?php echo htmlspecialchars((string)$m['id']); ?>">
                                            <button class="btn-pixel-yellow" type="submit">Completa</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <footer class="footer-ubq mt-5">
        <div class="container py-4">
            <div class="row gy-3 align-items-start">
                <div class="col-md-4">
                    <h3 class="h5 fw-bold mb-2 text-white">UniBoQuest</h3>
                    <p class="mb-1 small text-white opacity-75">Il gioco che trasforma la vita universitaria in una quest.</p>
                    <p class="small mb-0 text-white opacity-50">Progetto didattico – Università di Cesena.</p>
                </div>
                <div class="col-md-3">
                    <h4 class="h6 fw-bold mb-2 text-white">Navigazione</h4>
                    <ul class="list-unstyled small mb-0">
                        <li><a href="gioco.html" class="footer-link text-white text-decoration-none">Il Gioco</a></li>
                        <li><a href="faq.html" class="footer-link text-white text-decoration-none">FAQ</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h4 class="h6 fw-bold mb-2 text-white">Seguici</h4>
                    <nav class="footer-social" aria-label="Social Link">
                        <a href="#" class="social-icon" aria-label="Instagram">
                            <span class="bi bi-instagram" aria-hidden="true"></span>
                        </a>
                        <a href="#" class="social-icon" aria-label="Discord">
                            <span class="bi bi-discord" aria-hidden="true"></span>
                        </a>
                        <a href="https://github.com/LolloMarche25/uniboquest.git" class="social-icon" aria-label="GitHub">
                            <span class="bi bi-github" aria-hidden="true"></span>
                        </a>
                    </nav>
                </div>
            </div>
            <div class="footer-bottom border-top border-light-subtle mt-4 pt-3 text-center small text-secondary">
                <span>&copy; 2026 UniBoQuest – Prototipo Alpha.</span>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>