USE uniboquest;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS profiles (
  user_id INT NOT NULL PRIMARY KEY,
  nickname VARCHAR(32) NOT NULL,
  display_name VARCHAR(60) NULL,
  course VARCHAR(80) NULL,
  year_label VARCHAR(20) NULL,
  campus VARCHAR(40) NULL,
  bio VARCHAR(255) NULL,
  pref_events TINYINT(1) NOT NULL DEFAULT 0,
  pref_study  TINYINT(1) NOT NULL DEFAULT 0,
  pref_sport  TINYINT(1) NOT NULL DEFAULT 0,
  pref_social TINYINT(1) NOT NULL DEFAULT 0,
  avatar VARCHAR(30) NULL,
  privacy_public TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_profiles_nickname (nickname),
  
  CONSTRAINT fk_profiles_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS missions (
  id VARCHAR(50) NOT NULL PRIMARY KEY,
  sort_order INT NOT NULL DEFAULT 0,
  title VARCHAR(120) NOT NULL,
  subtitle VARCHAR(180) NULL,
  description TEXT NOT NULL,
  category ENUM('eventi','studio','social','sport') NOT NULL,
  difficulty ENUM('facile','media','difficile') NOT NULL,
  time_label VARCHAR(40) NULL,
  xp INT NOT NULL DEFAULT 0,
  requires_checkin TINYINT(1) NOT NULL DEFAULT 0,
  checkin_code VARCHAR(50) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO missions
(id, sort_order, title, subtitle, description, category, difficulty, time_label, xp, requires_checkin, checkin_code, active)
VALUES
('intro', 1, 'Prima Quest: Benvenuto in UniBoQuest', 'Sblocca il tuo percorso e ottieni i primi XP.',
 'Completa il tutorial per capire come funzionano missioni, livelli e check-in.',
 'social', 'facile', '5 min', 20, 0, NULL, 1),

('checkin', 2, 'Check-in Evento', 'Conferma la presenza con QR o codice fallback.',
 'Durante un evento, scansiona il QR mostrato dagli organizzatori. Se non puoi, inserisci un codice testuale equivalente.',
 'eventi', 'media', 'QR/Codice', 50, 1, 'UBQ-2026', 1),

('study', 3, 'Missione Studio: 25 minuti focus', 'Una sessione Pomodoro per guadagnare XP.',
 'Imposta un timer da 25 minuti e studia senza interruzioni. A fine sessione, segna la missione come completata.',
 'studio', 'facile', '25 min', 30, 0, NULL, 1),

('sport', 4, 'Allenamento Campus: 15 minuti', 'Mini circuito per XP extra.',
 'Completa un circuito leggero da 15 minuti. Nel prototipo, la prova è simulata.',
 'sport', 'difficile', '15 min', 70, 0, NULL, 1)
ON DUPLICATE KEY UPDATE
  title=VALUES(title),
  subtitle=VALUES(subtitle),
  description=VALUES(description),
  category=VALUES(category),
  difficulty=VALUES(difficulty),
  time_label=VALUES(time_label),
  xp=VALUES(xp),
  requires_checkin=VALUES(requires_checkin),
  checkin_code=VALUES(checkin_code),
  active=VALUES(active);

CREATE TABLE IF NOT EXISTS user_missions (
  user_id INT NOT NULL,
  mission_id VARCHAR(50) NOT NULL,
  status ENUM('active','completed') NOT NULL DEFAULT 'active',
  joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (user_id, mission_id),
  CONSTRAINT fk_um_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_um_mission FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
