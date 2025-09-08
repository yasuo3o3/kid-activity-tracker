<?php
declare(strict_types=1);

function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  $path = __DIR__ . '/../database.sqlite';
  $is_new = !file_exists($path);

  $pdo = new PDO('sqlite:' . $path, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);

  if ($is_new) {
    $pdo->exec("PRAGMA journal_mode = WAL;");
    $pdo->exec("CREATE TABLE IF NOT EXISTS kids (
      id TEXT PRIMARY KEY,
      display_name TEXT NOT NULL,
      archived INTEGER NOT NULL DEFAULT 0,
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );");
    $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
      id TEXT PRIMARY KEY,
      kid_id TEXT NOT NULL,
      label TEXT NOT NULL CHECK(label IN ('study','play','break')),
      started_at TEXT NOT NULL,
      ended_at TEXT,
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY(kid_id) REFERENCES kids(id) ON DELETE CASCADE
    );");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_sessions_kid_started ON sessions(kid_id, started_at);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_sessions_open ON sessions(kid_id, ended_at);");
  }

  return $pdo;
}

function uuid(): string {
  // 簡易UUID（SQLiteで生成しても良い）
  $data = random_bytes(16);
  $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
  $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}