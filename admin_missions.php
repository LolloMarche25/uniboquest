<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/admin_guard.php';
require __DIR__ . '/config/db.php';

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$allowedCat  = ['eventi','studio','social','sport'];
$allowedDiff = ['facile','media','difficile'];

$errors = [];
$flashOk = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');
  $id = strtolower(trim((string)($_POST['id'] ?? '')));

  if ($action !== 'create') {
    if ($id === '' || !preg_match('/^[a-z0-9_-]{1,50}$/', $id)) {
      $errors[] = "ID missione non valido.";
    }
  }

  if ($action === 'create') {
    $id = strtolower(trim((string)($_POST['id'] ?? '')));
    $title = trim((string)($_POST['title'] ?? ''));
    $subtitle = trim((string)($_POST['subtitle'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $category = (string)($_POST['category'] ?? '');
    $difficulty = (string)($_POST['difficulty'] ?? '');
    $timeLabel = trim((string)($_POST['time_label'] ?? ''));
    $xp = (int)($_POST['xp'] ?? 0);
    $requiresCheckin = isset($_POST['requires_checkin']) ? 1 : 0;
    $checkinCode = strtoupper(trim((string)($_POST['checkin_code'] ?? '')));
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $active = isset($_POST['active']) ? 1 : 0;

    if ($id === '' || !preg_match('/^[a-z0-9_-]{1,50}$/', $id)) {
      $errors[] = "ID non valido (usa solo a-z, 0-9, _ e -).";
    }
    if ($title === '' || mb_strlen($title) > 120) {
      $errors[] = "Titolo obbligatorio (max 120).";
    }
    if ($subtitle !== '' && mb_strlen($subtitle) > 180) {
      $errors[] = "Sottotitolo troppo lungo (max 180).";
    }
    if ($description === '') {
      $errors[] = "Descrizione obbligatoria.";
    }
    if (!in_array($category, $allowedCat, true)) {
      $errors[] = "Categoria non valida.";
    }
    if (!in_array($difficulty, $allowedDiff, true)) {
      $errors[] = "Difficoltà non valida.";
    }
    if ($xp < 0) {
      $errors[] = "XP non può essere negativo.";
    }
    if ($requiresCheckin === 1 && $checkinCode === '') {
      $errors[] = "Se richiede check-in, inserisci un codice check-in.";
    }
    if ($requiresCheckin === 0) {
      $checkinCode = null;
    }

    if (!$errors) {
      $stmt = $mysqli->prepare("SELECT 1 FROM missions WHERE id = ? LIMIT 1");
      $stmt->bind_param("s", $id);
      $stmt->execute();
      $stmt->store_result();
      $exists = ($stmt->num_rows > 0);
      $stmt->close();

      if ($exists) $errors[] = "Esiste già una missione con questo ID.";
    }

    if (!$errors) {
      $stmt = $mysqli->prepare("
        INSERT INTO missions
          (id, sort_order, title, subtitle, description, category, difficulty, time_label, xp,
           requires_checkin, checkin_code, active)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");

        $stmt->bind_param(
            "sissssssiisi",
            $id,
            $sortOrder,
            $title,
            $subtitle,
            $description,
            $category,
            $difficulty,
            $timeLabel,
            $xp,
            $requiresCheckin,
            $checkinCode,
            $active
        );

      if ($stmt->execute()) {
        $flashOk = "Missione creata.";
      } else {
        $errors[] = "Errore creazione missione.";
      }
      $stmt->close();
    }
  }

  if ($action === 'update' && !$errors) {
    $title = trim((string)($_POST['title'] ?? ''));
    $subtitle = trim((string)($_POST['subtitle'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $category = (string)($_POST['category'] ?? '');
    $difficulty = (string)($_POST['difficulty'] ?? '');
    $timeLabel = trim((string)($_POST['time_label'] ?? ''));
    $xp = (int)($_POST['xp'] ?? 0);
    $requiresCheckin = isset($_POST['requires_checkin']) ? 1 : 0;
    $checkinCode = strtoupper(trim((string)($_POST['checkin_code'] ?? '')));
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $active = isset($_POST['active']) ? 1 : 0;

    if ($title === '' || mb_strlen($title) > 120) $errors[] = "Titolo non valido.";
    if ($subtitle !== '' && mb_strlen($subtitle) > 180) $errors[] = "Sottotitolo troppo lungo.";
    if ($description === '') $errors[] = "Descrizione obbligatoria.";
    if (!in_array($category, $allowedCat, true)) $errors[] = "Categoria non valida.";
    if (!in_array($difficulty, $allowedDiff, true)) $errors[] = "Difficoltà non valida.";
    if ($xp < 0) $errors[] = "XP non può essere negativo.";
    if ($requiresCheckin === 1 && $checkinCode === '') $errors[] = "Manca check-in code.";
    if ($requiresCheckin === 0) $checkinCode = null;

    if (!$errors) {
      $stmt = $mysqli->prepare("
        UPDATE missions
        SET sort_order=?,
            title=?,
            subtitle=?,
            description=?,
            category=?,
            difficulty=?,
            time_label=?,
            xp=?,
            requires_checkin=?,
            checkin_code=?,
            active=?
        WHERE id=?
        LIMIT 1
      ");

      $stmt->bind_param(
        "issssssiisis",
        $sortOrder,
        $title,
        $subtitle,
        $description,
        $category,
        $difficulty,
        $timeLabel,
        $xp,
        $requiresCheckin,
        $checkinCode,
        $active,
        $id
       );

      if ($stmt->execute()) {
        $flashOk = "Missione aggiornata ($id).";
      } else {
        $errors[] = "Errore aggiornamento ($id).";
      }
      $stmt->close();
    }
  }

  if ($action === 'delete' && !$errors) {
    $stmt = $mysqli->prepare("DELETE FROM missions WHERE id = ? LIMIT 1");
    $stmt->bind_param("s", $id);

    if ($stmt->execute()) {
      $flashOk = "Missione eliminata ($id).";
    } else {
      $errors[] = "Errore eliminazione ($id).";
    }
    $stmt->close();
  }

  $q = $flashOk ? "?ok=1" : "";
  if ($errors) $q = "?err=1";
  header("Location: admin_missions.php$q");
  exit;
}

if (isset($_GET['ok']))  $flashOk = "Operazione completata.";
if (isset($_GET['err'])) $errors[] = "Operazione non riuscita. Controlla i campi e riprova.";

$res = $mysqli->query("
  SELECT id, sort_order, title, subtitle, description, category, difficulty, time_label, xp,
         requires_checkin, checkin_code, active
  FROM missions
  ORDER BY sort_order ASC, id ASC
");
$missions = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
if ($res) $res->close();
?>
<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="description" content="Admin: gestione missioni UniBoQuest." />

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Share+Tech+Mono&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="css/main.css" />
        <link rel="stylesheet" href="css/header.css" />

        <title>UniBoQuest - Admin Missioni</title>
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
                            <li class="nav-item"><a class="nav-link" href="missioni.php">MISSIONI</a></li>
                            <li class="nav-item"><a class="nav-link" href="profilo.php">PROFILO</a></li>
                            <li class="nav-item">
                                <a class="nav-link active" href="admin_missions.php" aria-current="page">ADMIN</a>
                            </li>
                        </ul>

                        <div class="d-flex gap-2 ubq-nav-right">
                            <a class="btn-pixel-yellow" href="logout.php">Esci</a>
                        </div>
                    </div>
                </div>
            </nav>
        </header>

        <main id="contenuto" class="container mt-4">
            <div class="missioni-card">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <h1 class="missioni-title font-8bit h2" style="font-size:1.15rem;">ADMIN • MISSIONI</h1>
                    <p class="missioni-subtitle mb-0">Crea, modifica ed elimina missioni.</p>
                </div>
                <span class="missioni-badge"><?php echo count($missions); ?> totali</span>
                </div>

                <hr class="my-4" style="border-color: rgba(255,255,255,.15);">

                <?php if (!empty($errors)): ?>
                <div class="checkin-msg err" style="margin-bottom: 1rem;" role="alert">
                    <?php foreach ($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?>
                </div>
                <?php elseif ($flashOk): ?>
                <div class="checkin-msg ok" style="margin-bottom: 1rem;" role="status">
                    <?php echo h($flashOk); ?>
                </div>
                <?php endif; ?>

                <section class="missioni-panel mb-3">
                    <h2 class="font-8bit mb-3 h6" style="color: #ffca2c; background-color: #1a1a1a; padding: 10px; display: inline-block; border-radius: 4px;">
                        AGGIUNGI NUOVA MISSIONE
                    </h2>

                    <form method="post" action="admin_missions.php">
                        <input type="hidden" name="action" value="create">

                        <div class="row g-3">
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="new_id">ID *</label>
                            <input class="form-control p-3" id="new_id" name="id" placeholder="es. biblioteca" required>
                            <div class="form-text">a-z 0-9 _ -</div>
                        </div>

                        <div class="col-12 col-md-5">
                            <label class="form-label" for="new_title">Titolo *</label>
                            <input class="form-control p-3" id="new_title" name="title" required>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label" for="new_subtitle">Sottotitolo</label>
                            <input class="form-control p-3" id="new_subtitle" name="subtitle">
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="new_desc">Descrizione *</label>
                            <textarea class="form-control p-3" id="new_desc" name="description" rows="3" required></textarea>
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label" for="new_cat">Categoria</label>
                            <select class="form-select p-3" id="new_cat" name="category" required>
                                <option value="" selected disabled>Seleziona categoria</option>
                            <?php foreach ($allowedCat as $c): ?>
                                <option value="<?php echo h($c); ?>"><?php echo h(ucfirst($c)); ?></option>
                            <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label" for="new_diff">Difficoltà</label>
                            <select class="form-select p-3" id="new_diff" name="difficulty" required>
                                <option value="" selected disabled>Seleziona difficoltà</option>
                            <?php foreach ($allowedDiff as $d): ?>
                                <option value="<?php echo h($d); ?>"><?php echo h(ucfirst($d)); ?></option>
                            <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label" for="new_xp">XP</label>
                            <input class="form-control p-3" type="number" id="new_xp" name="xp" value="0" min="0">
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label" for="new_order">Ordine</label>
                            <input class="form-control p-3" type="number" id="new_order" name="sort_order" value="0">
                        </div>

                        <div class="col-12 col-md-2">
                            <label class="form-label" for="new_time">Tempo</label>
                            <input class="form-control p-3" id="new_time" name="time_label" placeholder="es. 10 min">
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="req" name="requires_checkin">
                                <label class="form-check-label" for="req">Richiede check-in</label>
                            </div>
                            <label for="new_code" class="visually-hidden">Codice Checkin</label>
                            <input class="form-control p-3 mt-2" id="new_code" name="checkin_code" placeholder="CHECKIN CODE (se richiesto)">
                        </div>

                        <div class="col-12 col-md-2">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="act" name="active" checked>
                                <label class="form-check-label" for="act">Attiva</label>
                            </div>
                        </div>

                        <div class="col-12 d-grid">
                            <button class="btn-pixel-yellow" type="submit">Crea missione</button>
                        </div>
                        </div>
                    </form>
                </section>

                <section class="d-grid gap-3">
                    <h2 class="visually-hidden">Lista Missioni Esistenti</h2>
                <?php foreach ($missions as $m): ?>
                    <?php $safe_id = h((string)$m['id']); ?>
                    <article class="missioni-panel">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <h3 class="mission-title h6 mb-0" style="color:var(--ubq-gold); font-weight:bold;">
                            <?php echo $safe_id; ?> • <?php echo h((string)$m['title']); ?>
                        </h3>
                        <span class="mission-badge">+<?php echo (int)$m['xp']; ?> XP</span>
                    </div>

                    <form method="post" action="admin_missions.php" class="mt-3">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?php echo $safe_id; ?>">

                        <div class="row g-3">
                        <div class="col-12 col-md-2">
                            <label class="form-label small text-white-50" for="sort_<?php echo $safe_id; ?>">Ordine</label>
                            <input id="sort_<?php echo $safe_id; ?>" class="form-control p-2" type="number" name="sort_order" value="<?php echo (int)$m['sort_order']; ?>">
                        </div>

                        <div class="col-12 col-md-7">
                            <label class="form-label small text-white-50" for="title_<?php echo $safe_id; ?>">Titolo</label>
                            <input id="title_<?php echo $safe_id; ?>" class="form-control p-2" name="title" value="<?php echo h((string)$m['title']); ?>">
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label small text-white-50" for="xp_<?php echo $safe_id; ?>">XP</label>
                            <input id="xp_<?php echo $safe_id; ?>" class="form-control p-2" type="number" name="xp" min="0" value="<?php echo (int)$m['xp']; ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small text-white-50" for="sub_<?php echo $safe_id; ?>">Sottotitolo</label>
                            <input id="sub_<?php echo $safe_id; ?>" class="form-control p-2" name="subtitle" value="<?php echo h((string)($m['subtitle'] ?? '')); ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small text-white-50" for="time_<?php echo $safe_id; ?>">Tempo</label>
                            <input id="time_<?php echo $safe_id; ?>" class="form-control p-2" name="time_label" value="<?php echo h((string)($m['time_label'] ?? '')); ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label small text-white-50" for="desc_<?php echo $safe_id; ?>">Descrizione</label>
                            <textarea id="desc_<?php echo $safe_id; ?>" class="form-control p-2" name="description" rows="3"><?php echo h((string)$m['description']); ?></textarea>
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label small text-white-50" for="cat_<?php echo $safe_id; ?>">Categoria</label>
                            <select id="cat_<?php echo $safe_id; ?>" class="form-select p-2" name="category">
                            <?php foreach ($allowedCat as $c): ?>
                                <option value="<?php echo h($c); ?>" <?php echo ($m['category'] === $c ? 'selected' : ''); ?>>
                                <?php echo h(ucfirst($c)); ?>
                                </option>
                            <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label small text-white-50" for="diff_<?php echo $safe_id; ?>">Difficoltà</label>
                            <select id="diff_<?php echo $safe_id; ?>" class="form-select p-2" name="difficulty">
                            <?php foreach ($allowedDiff as $d): ?>
                                <option value="<?php echo h($d); ?>" <?php echo ($m['difficulty'] === $d ? 'selected' : ''); ?>>
                                <?php echo h(ucfirst($d)); ?>
                                </option>
                            <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-3">
                            <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox"
                                    id="rc_<?php echo $safe_id; ?>"
                                    name="requires_checkin"
                                    <?php echo ((int)$m['requires_checkin'] === 1 ? 'checked' : ''); ?>>
                            <label class="form-check-label" for="rc_<?php echo $safe_id; ?>">Check-in</label>
                            </div>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label small text-white-50" for="code_<?php echo $safe_id; ?>">Codice</label>
                            <input id="code_<?php echo $safe_id; ?>" class="form-control p-2" name="checkin_code" value="<?php echo h((string)($m['checkin_code'] ?? '')); ?>">
                        </div>

                        <div class="col-6 col-md-2">
                            <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox"
                                    id="ac_<?php echo $safe_id; ?>"
                                    name="active"
                                    <?php echo ((int)$m['active'] === 1 ? 'checked' : ''); ?>>
                            <label class="form-check-label" for="ac_<?php echo $safe_id; ?>">Attiva</label>
                            </div>
                        </div>

                        <div class="col-12 col-md-10 d-flex gap-2 justify-content-end">
                            <button class="btn-pixel-yellow" type="submit">Salva</button>
                        </div>
                        </div>
                    </form>

                    <div class="d-flex justify-content-end mt-2">
                        <form method="post" action="admin_missions.php"
                                onsubmit="return confirm('Sei sicuro? Questa missione verrà eliminata definitivamente.');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $safe_id; ?>">
                            <button class="btn-pixel" type="submit">Elimina</button>
                        </form>
                    </div>
                    </article>
                <?php endforeach; ?>
                </section>

            </div>
        </main>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>