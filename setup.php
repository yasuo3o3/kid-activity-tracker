<?php
// 初期化スクリプト（一回だけ実行）
// 子供のデータをデータベースに登録し、UUIDを生成する

require_once 'lib/db.php';

// 登録したい子供の名前
$kids = [
  'やすお君',
  'みさきちゃん'
];

echo "=== Kid Activity Tracker 初期化 ===\n\n";

try {
  $pdo = db();
  
  // 既存のデータを確認
  $existing = $pdo->query("SELECT COUNT(*) as count FROM kids")->fetch();
  if ($existing['count'] > 0) {
    echo "既に子供のデータが登録されています。\n";
    echo "現在の登録:\n";
    $stmt = $pdo->query("SELECT id, display_name FROM kids ORDER BY created_at");
    while ($row = $stmt->fetch()) {
      echo "  - {$row['display_name']}: {$row['id']}\n";
    }
    echo "\n続行しますか？ (y/N): ";
    $input = trim(fgets(STDIN));
    if (strtolower($input) !== 'y') {
      echo "キャンセルしました。\n";
      exit(0);
    }
  }

  echo "子供のデータを登録します...\n\n";
  
  foreach ($kids as $name) {
    // 既に同じ名前が存在するかチェック
    $stmt = $pdo->prepare("SELECT id FROM kids WHERE display_name = ? AND archived = 0");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
      echo "  - {$name}: 既に登録済みです（スキップ）\n";
      continue;
    }
    
    // 新規登録
    $id = uuid();
    $stmt = $pdo->prepare("INSERT INTO kids (id, display_name) VALUES (?, ?)");
    $stmt->execute([$id, $name]);
    
    echo "  - {$name}: {$id}\n";
  }
  
  echo "\n=== 登録完了 ===\n";
  echo "以下をconfig.phpの'kids'配列にコピーしてください:\n\n";
  echo "'kids' => [\n";
  
  $stmt = $pdo->query("SELECT id, display_name FROM kids WHERE archived = 0 ORDER BY created_at");
  while ($row = $stmt->fetch()) {
    echo "  '{$row['id']}' => '{$row['display_name']}',\n";
  }
  echo "],\n\n";
  
  echo "各子供専用のURL:\n";
  $stmt = $pdo->query("SELECT id, display_name FROM kids WHERE archived = 0 ORDER BY created_at");
  while ($row = $stmt->fetch()) {
    echo "  - {$row['display_name']}: ?kid={$row['id']}\n";
  }
  
} catch (Exception $e) {
  echo "エラーが発生しました: " . $e->getMessage() . "\n";
  exit(1);
}