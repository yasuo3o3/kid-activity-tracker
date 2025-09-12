<?php
// 最小のPWA UI（3ボタン＋kid_id保存）
?><!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Kid Activity Tracker</title>
  <meta name="theme-color" content="#0ea5e9" />
  <base href="/kid-activity-tracker/">
  <meta name="app-base" content="/kid-activity-tracker/">
  <link rel="manifest" href="./pwa/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <link rel="stylesheet" href="./style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <header class="wrap">
    <h1 id="title">これから何する？</h1>
  </header>
  <main class="wrap">
    <!-- 子ども用画面のボタン -->
    <div id="kid-screen" class="buttons">
      <div class="activity-row">
        <button id="study-btn" class="btn study" onclick="startActivity('study')">勉強する</button>
        <button id="study-stop" class="btn study-stop" onclick="stopActivity()" disabled>終了</button>
      </div>
      <div class="activity-row">
        <button id="play-btn" class="btn play" onclick="startActivity('play')">遊ぶ</button>
        <button id="play-stop" class="btn play-stop" onclick="stopActivity()" disabled>終了</button>
      </div>
      <div class="activity-row">
        <button id="break-btn" class="btn break" onclick="startActivity('break')">休憩する</button>
        <button id="break-stop" class="btn break-stop" onclick="stopActivity()" disabled>終了</button>
      </div>
    </div>

    <!-- 子ども用画面の状態表示 -->
    <section id="kid-status" class="status">
      <div id="now">今：—</div>
      <div id="today">今日：— 分</div>
    </section>

    <section id="kid-activity-totals" class="activity-totals">
      <div id="study-total" class="activity-total study-bg">勉強：— 分</div>
      <div id="play-total" class="activity-total play-bg">遊び：— 分</div>
      <div id="break-total" class="activity-total break-bg">休憩：— 分</div>
    </section>

    <section id="kid-weekly-monthly" class="weekly-monthly">
      <div id="week-total" class="period-total">【今週】勉強：— 分 ／ 遊び：— 分 ／ 休憩：— 分</div>
      <div id="month-total" class="period-total">【今月】勉強：— 分 ／ 遊び：— 分 ／ 休憩：— 分</div>
    </section>

    <div class="note" id="note">※ URLに ?kid=UUID を指定してください。</div>
  </main>
<script src="./assets/url-helper.js"></script>
<script src="./assets/copy-link.js?v=1"></script>
<script>
  // publicUrl() を使用してAPI URLを生成
  const api = (p) => publicUrl(`./api/${p}`);

  // SweetAlert2 ラッパー関数
  const notify = {
    success: (message) => {
      return Swal.fire({
        icon: 'success',
        title: '成功',
        text: message,
        timer: 2000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
      });
    },
    error: (message) => {
      return Swal.fire({
        icon: 'error',
        title: 'エラー',
        text: message,
        confirmButtonText: 'OK'
      });
    },
    info: (message) => {
      return Swal.fire({
        icon: 'info',
        title: '情報',
        text: message,
        confirmButtonText: 'OK'
      });
    },
    confirm: (message, options = {}) => {
      return Swal.fire({
        icon: 'question',
        title: '確認',
        text: message,
        showCancelButton: true,
        confirmButtonText: options.confirmText || 'はい',
        cancelButtonText: options.cancelText || 'いいえ',
        ...options
      }).then((result) => result.isConfirmed);
    }
  };

  function getKidId() {
    const params = new URLSearchParams(location.search);
    return params.get('kid') || '';
  }
  async function startActivity(label) {
    const kid_id = getKidId();
    if (!kid_id) return notify.error("URLに ?kid=UUID を指定してください");
    try {
      const r = await fetch(api("switch.php"), {
        method:"POST",
        headers:{ "Content-Type":"application/json" },
        body: JSON.stringify({ kid_id, label })
      });
      const j = await r.json();
      if (!r.ok || !j.ok) throw new Error(j.error || "failed");
      await refresh();
      notify.success(`${jp(label)}を開始しました`);
    } catch (e) {
      notify.error("エラー: " + e.message);
    }
  }

  async function stopActivity() {
    const kid_id = getKidId();
    if (!kid_id) return notify.error("URLに ?kid=UUID を指定してください");
    try {
      const r = await fetch(api("stop.php"), {
        method:"POST",
        headers:{ "Content-Type":"application/json" },
        body: JSON.stringify({ kid_id })
      });
      const j = await r.json();
      if (!r.ok || !j.ok) throw new Error(j.error || "failed");
      await refresh();
      notify.success("終了しました");
    } catch (e) {
      notify.error("エラー: " + e.message);
    }
  }
  function updateButtons(currentLabel) {
    const activities = ['study', 'play', 'break'];
    activities.forEach(activity => {
      const startBtn = document.getElementById(`${activity}-btn`);
      const stopBtn = document.getElementById(`${activity}-stop`);
      
      if (currentLabel === activity) {
        startBtn.disabled = true;
        stopBtn.disabled = false;
      } else {
        startBtn.disabled = false;
        stopBtn.disabled = true;
      }
    });
  }

  async function refresh() {
    const kid_id = getKidId();
    if (!kid_id) return;
    const r = await fetch(api(`stats.php?kid_id=${encodeURIComponent(kid_id)}`), { method:"GET" });
    const j = await r.json();
    if (j.ok) {
      document.getElementById("now").textContent =
        j.now.label ? `今：${jp(j.now.label)}（${toJstHHmm(j.now.since)}開始）` : "今：—";
      document.getElementById("today").textContent =
        `今日：${formatTime(j.totals.today_sec||0)}`;
      
      // 活動別累計時間を表示
      document.getElementById("study-total").textContent =
        `勉強：${formatTime(j.today_by_activity.study_sec||0)}`;
      document.getElementById("play-total").textContent =
        `遊び：${formatTime(j.today_by_activity.play_sec||0)}`;
      document.getElementById("break-total").textContent =
        `休憩：${formatTime(j.today_by_activity.break_sec||0)}`;
      
      // 今週・今月の活動別累計時間を表示
      document.getElementById("week-total").textContent =
        `【今週】勉強：${formatTime(j.week_by_activity.study_sec||0)} ／ 遊び：${formatTime(j.week_by_activity.play_sec||0)} ／ 休憩：${formatTime(j.week_by_activity.break_sec||0)}`;
      document.getElementById("month-total").textContent =
        `【今月】勉強：${formatTime(j.month_by_activity.study_sec||0)} ／ 遊び：${formatTime(j.month_by_activity.play_sec||0)} ／ 休憩：${formatTime(j.month_by_activity.break_sec||0)}`;
      
      updateButtons(j.now.label || null);
    }
  }
  function jp(label){ return label==="study"?"勉強":label==="play"?"遊び":"休憩"; }
  function toJstHHmm(iso){
    const d = new Date(iso);
    const j = new Date(d.getTime() + 9*3600*1000);
    return String(j.getUTCHours()).padStart(2,"0")+":"+String(j.getUTCMinutes()).padStart(2,"0");
  }
  function formatTime(seconds){
    const minutes = Math.ceil(seconds / 60);
    if (minutes < 60) {
      return `${minutes}分`;
    }
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    if (remainingMinutes === 0) {
      return `${hours}時間`;
    }
    return `${hours}時間${remainingMinutes}分`;
  }
  async function initPage() {
    const kid_id = getKidId();
    if (kid_id) {
      // kid_idから名前を取得してタイトルを設定
      try {
        const r = await fetch(api(`stats.php?kid_id=${encodeURIComponent(kid_id)}`), { method:"GET" });
        const j = await r.json();
        if (j.ok && j.kid_name) {
          document.getElementById("title").textContent = `${j.kid_name}：これから何する？`;
          document.getElementById("note").textContent = `※ ${j.kid_name}専用画面です。`;
        }
      } catch (e) {
        console.error("名前の取得に失敗:", e);
      }
      refresh();
    } else {
      // kid_idが指定されていない場合
      document.getElementById("title").textContent = "URLパラメータが必要です";
      document.getElementById("note").innerHTML = `※ URLに ?kid=UUID を指定してください。<br>管理画面は <a href="admin.php">こちら</a> からアクセスできます。`;
      document.getElementById("kid-screen").style.display = "none";
      document.getElementById("kid-status").style.display = "none";
      document.getElementById("kid-activity-totals").style.display = "none";
      document.getElementById("kid-weekly-monthly").style.display = "none";
    }
    if ("serviceWorker" in navigator) navigator.serviceWorker.register(publicUrl('./pwa/service-worker.js'));
  }



  window.addEventListener("load", initPage);
</script>

</body>
</html>