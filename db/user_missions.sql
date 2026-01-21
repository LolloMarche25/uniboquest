USE uniboquest;

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
