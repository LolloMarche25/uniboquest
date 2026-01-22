<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/profile_guard.php';
require __DIR__ . '/config/db.php';

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');
  $mid = trim((string)($_POST['mission_id'] ?? ''));

  if ($mid !== '' && preg_match('/^[a-z0-9_-]{1,50}$/i', $mid)) {
    if ($action === 'join') {
      $stmt = $mysqli->prepare("
        INSERT INTO user_missions (user_id, mission_id, status)
        VALUES (?, ?, 'active')
        ON DUPLICATE KEY UPDATE 
            status = IF(status='completed','completed','active'),
            completed_at = IF(status='completed', completed_at, NULL)
      ");
      $stmt->bind_param("is", $userId, $mid);
      $stmt->execute();
      $stmt->close();
    }

    if ($action === 'abandon') {
      $stmt = $mysqli->prepare("DELETE FROM user_missions WHERE user_id = ? AND mission_id = ?");
      $stmt->bind_param("is", $userId, $mid);
      $stmt->execute();
      $stmt->close();
    }
  }

  header('Location: missioni.php');
  exit;
}

$res = $mysqli->query("
  SELECT id, title, description, category, difficulty, time_label, xp, requires_checkin
  FROM missions
  WHERE active = 1
  ORDER BY sort_order ASC, title ASC
");
$missions = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
if ($res) $res->close();

$stmt = $mysqli->prepare("SELECT mission_id, status FROM user_missions WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$state = [];
foreach ($rows as $r) {
  $state[(string)$r['mission_id']] = (string)$r['status'];
}

function normCat(string $c): string {
  $c = strtolower(trim($c));
  if ($c === 'eventi' || $c === 'evento') return 'eventi';
  if ($c === 'studio') return 'studio';
  if ($c === 'sport') return 'sport';
  return 'social';
}

function normDiff(string $d): string {
  $d = strtolower(trim($d));
  if ($d === 'facile') return 'facile';
  if ($d === 'media') return 'media';
  return 'difficile';
}

$missionCount = count($missions);
?>
<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="description" content="Missioni UniBoQuest (area privata)." />

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Share+Tech+Mono&display=swap" rel="stylesheet" />

        <link rel="stylesheet" href="css/main.css" />

        <title>UniBoQuest - Missioni</title>
    </head>

    <body class="manual-bg missioni-page">
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
                            <li class="nav-item"><a class="nav-link" href="dashboard.php">DASHBOARD</a></li>
                            <li class="nav-item"><a class="nav-link active" href="missioni.php" aria-current="page">MISSIONI</a></li>
                            <li class="nav-item"><a class="nav-link" href="profilo.php">PROFILO</a></li>
                            <?php if (($_SESSION['user_role'] ?? 'user') === 'admin'): ?>
                              <li class="nav-item">
                                <a class="nav-link" href="admin_missions.php">ADMIN</a>
                              </li>
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
            <div class="missioni-card">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div>
                        <h1 class="missioni-title font-8bit h2" style="font-size: 1.15rem;">MISSIONI</h1>
                        <p class="missioni-subtitle mb-0">Scegli una quest e guadagna XP.</p>
                    </div>

                    <span class="missioni-badge" id="resultBadge">
                      <?php echo $missionCount . ($missionCount === 1 ? " missione" : " missioni"); ?>
                    </span>
                </div>

                <hr class="my-4" style="border-color: rgba(255,255,255,.15);">

                <section class="missioni-panel mb-3" aria-label="Filtri ricerca">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-5">
                            <label for="q" class="form-label text-white">Cerca</label>
                            <input id="q" type="text" class="form-control p-3" placeholder="Es. check-in, studio, evento…" />
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="cat" class="form-label text-white">Categoria</label>
                            <select id="cat" class="form-select p-3">
                                <option value="all">Tutte</option>
                                <option value="eventi">Eventi</option>
                                <option value="studio">Studio</option>
                                <option value="social">Social</option>
                                <option value="sport">Sport</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="diff" class="form-label text-white">Difficoltà</label>
                            <select id="diff" class="form-select p-3">
                                <option value="all">Tutte</option>
                                <option value="facile">Facile</option>
                                <option value="media">Media</option>
                                <option value="difficile">Difficile</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-2 d-grid">
                            <button type="button" class="btn-pixel" id="resetBtn">Reset</button>
                        </div>
                    </div>
                </section>

                <div class="d-grid gap-3" id="missionList">
                  <?php foreach ($missions as $m):
                    $mid = (string)$m['id'];
                    $title = (string)$m['title'];
                    $desc = (string)$m['description'];
                    $cat = normCat((string)$m['category']);
                    $diff = normDiff((string)$m['difficulty']);
                    $xp = (int)$m['xp'];
                    $timeLabel = (string)($m['time_label'] ?? '');
                    $requiresCheckin = ((int)$m['requires_checkin'] === 1);

                    $st = $state[$mid] ?? '';
                    $isActive = ($st === 'active');
                    $isCompleted = ($st === 'completed');
                  ?>
                    <article class="mission-card"
                      data-title="<?php echo htmlspecialchars($title); ?>"
                      data-cat="<?php echo htmlspecialchars($cat); ?>"
                      data-diff="<?php echo htmlspecialchars($diff); ?>"
                    >
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <h2 class="mission-title h6 mb-0"><?php echo htmlspecialchars($title); ?></h2>
                            <span class="mission-pill xp">+<?php echo $xp; ?> XP</span>
                        </div>

                        <p class="mission-desc"><?php echo htmlspecialchars($desc); ?></p>

                        <div class="mission-meta">
                            <span class="mission-pill">Categoria: <?php echo htmlspecialchars(ucfirst($cat)); ?></span>
                            <span class="mission-pill">Difficoltà: <?php echo htmlspecialchars(ucfirst($diff)); ?></span>
                            <?php if ($timeLabel !== ''): ?>
                              <span class="mission-pill">Tempo: <?php echo htmlspecialchars($timeLabel); ?></span>
                            <?php endif; ?>
                            <?php if ($requiresCheckin): ?>
                              <span class="mission-pill">Metodo: QR/Codice</span>
                            <?php endif; ?>

                            <?php if ($isCompleted): ?>
                              <span class="mission-pill">Stato: Completata</span>
                            <?php elseif ($isActive): ?>
                              <span class="mission-pill">Stato: In corso</span>
                            <?php endif; ?>
                        </div>

                        <div class="mission-actions">
                            <?php if ($requiresCheckin): ?>
                              <a class="btn-pixel-yellow" href="checkin.php?id=<?php echo urlencode($mid); ?>">Vai al check-in</a>
                            <?php else: ?>
                              <a class="btn-pixel-yellow" href="missione_dettaglio.php?id=<?php echo urlencode($mid); ?>">Dettagli</a>
                            <?php endif; ?>

                            <?php if ($isCompleted): ?>
                              <button class="btn-pixel" type="button" disabled>Completata</button>
                            <?php elseif ($isActive): ?>
                              <form method="post" action="missioni.php" style="display:inline;">
                                <input type="hidden" name="action" value="abandon">
                                <input type="hidden" name="mission_id" value="<?php echo htmlspecialchars($mid); ?>">
                                <button class="btn-pixel" type="submit">Abbandona</button>
                              </form>
                            <?php else: ?>
                              <form method="post" action="missioni.php" style="display:inline;">
                                <input type="hidden" name="action" value="join">
                                <input type="hidden" name="mission_id" value="<?php echo htmlspecialchars($mid); ?>">
                                <button class="btn-pixel" type="submit">Partecipa</button>
                              </form>
                            <?php endif; ?>
                        </div>
                    </article>
                  <?php endforeach; ?>
                </div>

                <div id="emptyState" class="mission-empty mt-3 d-none">
                    Nessuna missione trovata. Prova a cambiare filtri o ricerca.
                </div>
            </div>
        </main>

        <footer class="footer-ubq mt-5">
            <div class="container py-4">
                <div class="row gy-3 align-items-start">
                    <div class="col-md-4">
                        <h2 class="h5 fw-bold mb-2 text-white">UniBoQuest</h2>
                        <p class="mb-1 small text-white opacity-75">Il gioco che trasforma la vita universitaria in una quest.</p>
                        <p class="small mb-0 text-white opacity-50">Progetto didattico – Università di Cesena.</p>
                    </div>

                    <div class="col-md-3">
                        <h2 class="h6 fw-bold mb-2 text-white">Navigazione</h2>
                        <ul class="list-unstyled small mb-0">
                            <li><a href="gioco.html" class="footer-link text-white text-decoration-none">Il Gioco</a></li>
                            <li><a href="faq.html" class="footer-link text-white text-decoration-none">FAQ</a></li>
                        </ul>
                    </div>

                    <div class="col-md-3">
                        <h2 class="h6 fw-bold mb-2 text-white">Seguici</h2>
                        <nav class="footer-social" aria-label="Social">
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

                <div class="footer-bottom border-top border-light-subtle mt-4 pt-3 d-flex flex-column flex-md-row justify-content-between align-items-center small text-secondary">
                    <span>&copy; 2026 UniBoQuest – Prototipo Alpha.</span>
                </div>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="js/missioni.js"></script>
    </body>
</html>