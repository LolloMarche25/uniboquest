<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/profile_guard.php';
require __DIR__ . '/config/db.php';

$userId = (int)$_SESSION['user_id'];

$id = trim((string)($_GET['id'] ?? ''));
if ($id === '' || !preg_match('/^[a-z0-9_-]{1,50}$/i', $id)) {
  header('Location: missioni.php');
  exit;
}

$stmt = $mysqli->prepare("
  SELECT id, title, subtitle, description, category, difficulty, time_label, xp, requires_checkin, active
  FROM missions
  WHERE id = ? AND active = 1
  LIMIT 1
");
$stmt->bind_param("s", $id);
$stmt->execute();
$mission = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$mission) {
  header('Location: missioni.php');
  exit;
}

$title = (string)$mission['title'];
$subtitle = (string)($mission['subtitle'] ?? '');
$desc = (string)$mission['description'];
$cat = (string)$mission['category'];
$diff = (string)$mission['difficulty'];
$timeLabel = (string)($mission['time_label'] ?? '');
$xp = (int)$mission['xp'];
$requiresCheckin = ((int)$mission['requires_checkin'] === 1);

$stmt = $mysqli->prepare("
  SELECT status
  FROM user_missions
  WHERE user_id = ? AND mission_id = ?
  LIMIT 1
");
$stmt->bind_param("is", $userId, $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$status = (string)($row['status'] ?? '');
$isActive = ($status === 'active');
$isCompleted = ($status === 'completed');

$errors = [];
$successMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');

  if ($action === 'join' && !$isCompleted) {
    $stmt = $mysqli->prepare("
      INSERT INTO user_missions (user_id, mission_id, status)
      VALUES (?, ?, 'active')
      ON DUPLICATE KEY UPDATE status = IF(status='completed','completed','active'), completed_at = NULL
    ");
    $stmt->bind_param("is", $userId, $id);
    $stmt->execute();
    $stmt->close();

    $isActive = true;
    $status = 'active';
    $successMsg = "Missione aggiunta: ora è In corso.";
  }

  if ($action === 'abandon' && !$isCompleted) {
    $stmt = $mysqli->prepare("DELETE FROM user_missions WHERE user_id = ? AND mission_id = ?");
    $stmt->bind_param("is", $userId, $id);
    $stmt->execute();
    $stmt->close();

    $isActive = false;
    $status = '';
    $successMsg = "Missione abbandonata.";
  }

  if ($action === 'complete' && $isActive && !$isCompleted) {
    $stmt = $mysqli->prepare("
      UPDATE user_missions
      SET status='completed', completed_at = NOW()
      WHERE user_id = ? AND mission_id = ?
    ");
    $stmt->bind_param("is", $userId, $id);
    $stmt->execute();
    $stmt->close();

    $isCompleted = true;
    $status = 'completed';
    $successMsg = "Missione completata! +{$xp} XP.";
  }
}

$badge = 'Disponibile';
if ($isCompleted) $badge = 'Completata';
elseif ($isActive) $badge = 'In corso';
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Dettaglio missione UniBoQuest." />

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Share+Tech+Mono&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="css/main.css" />

  <title>UniBoQuest - Dettaglio Missione</title>
</head>

<body class="manual-bg missione-page">
<a href="#contenuto" class="skip-link">Salta al contenuto principale</a>

<header class="header-glass">
  <nav class="navbar navbar-expand-md navbar-dark">
    <div class="container-fluid">
      <a class="navbar-brand font-8bit" href="dashboard.php">UniBoQuest</a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav mx-auto mb-2 mb-md-0 ubq-nav-center">
          <li class="nav-item"><a class="nav-link" href="dashboard.php">DASHBOARD</a></li>
          <li class="nav-item"><a class="nav-link active" href="missioni.php">MISSIONI</a></li>
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

<main id="contenuto" class="container">
  <div class="missione-card">

    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
      <div>
        <h1 class="missione-title font-8bit h2" style="font-size:1.05rem;">
          <?php echo htmlspecialchars(mb_strtoupper($title, 'UTF-8')); ?>
        </h1>
        <p class="missione-subtitle mb-0">
          <?php echo htmlspecialchars($subtitle !== '' ? $subtitle : 'Dettagli missione'); ?>
        </p>
      </div>
      <span class="missione-badge"><?php echo htmlspecialchars($badge); ?></span>
    </div>

    <hr class="my-4" style="border-color:rgba(255,255,255,.15);">

    <?php if ($successMsg !== ''): ?>
      <div class="missione-alert mb-3"><?php echo htmlspecialchars($successMsg); ?></div>
    <?php endif; ?>

    <div class="row g-3">
      <div class="col-12 col-lg-7">
        <div class="missione-panel">
          <p class="missione-text"><?php echo nl2br(htmlspecialchars($desc)); ?></p>

          <div class="missione-kv">
            <span class="missione-pill">Categoria: <?php echo htmlspecialchars(ucfirst($cat)); ?></span>
            <span class="missione-pill">Difficoltà: <?php echo htmlspecialchars(ucfirst($diff)); ?></span>
            <?php if ($timeLabel !== ''): ?>
              <span class="missione-pill">Tempo: <?php echo htmlspecialchars($timeLabel); ?></span>
            <?php endif; ?>
            <span class="missione-pill xp">Ricompensa: +<?php echo $xp; ?> XP</span>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-5">
        <div class="missione-panel">
          <h2 class="h3 font-8bit" style="font-size:1.5rem;">
            <span style="background:#000;color:#fff;padding:4px 8px;display:inline-block;">
              AZIONI
            </span>
          </h2>

          <div class="missione-actions mt-3">
            <a class="btn-pixel" href="missioni.php">Torna alle missioni</a>

            <?php if ($requiresCheckin): ?>
              <a class="btn-pixel-yellow" href="checkin.php?id=<?php echo urlencode($id); ?>">Vai al check-in</a>
            <?php else: ?>
              <?php if ($isCompleted): ?>
                <button class="btn-pixel-yellow" disabled>Completata</button>
              <?php elseif ($isActive): ?>
                <form method="post" style="display:inline;">
                  <input type="hidden" name="action" value="abandon">
                  <button class="btn-pixel">Abbandona</button>
                </form>
                <form method="post" style="display:inline;">
                  <input type="hidden" name="action" value="complete">
                  <button class="btn-pixel-yellow">Completa</button>
                </form>
              <?php else: ?>
                <form method="post" style="display:inline;">
                  <input type="hidden" name="action" value="join">
                  <button class="btn-pixel-yellow">Partecipa</button>
                </form>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="missione-panel mt-3">
          <h2 class="h3 font-8bit" style="font-size:1.5rem;">
            <span style="background:#000;color:#fff;padding:4px 8px;display:inline-block;">
              RICOMPENSA
            </span>
          </h2>
          <p class="missione-text mt-2">XP ottenuti completando la missione:</p>
          <span class="missione-pill xp">+<?php echo $xp; ?> XP</span>
        </div>
      </div>
    </div>
  </div>
</main>

<footer class="footer-ubq mt-5">
  <div class="container py-4">
    <div class="row gy-3">
      <div class="col-md-4">
        <h2 class="h5 fw-bold text-white">UniBoQuest</h2>
        <p class="small text-white opacity-75">Progetto didattico – Università di Cesena.</p>
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