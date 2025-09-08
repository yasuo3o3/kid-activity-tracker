<?php
// 最小のPWA UI（3ボタン＋kid_id保存）
?><!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Kid Activity Tracker</title>
  <meta name="theme-color" content="#0ea5e9" />
  <link rel="manifest" href="/kid-activity-tracker/pwa/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <style>
    :root { color-scheme: light dark; }
    body { margin:0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; }
    .wrap { max-width:640px; margin:0 auto; padding:16px; }
    h1 { font-size:1.6rem; text-align:center; margin:12px 0 8px; }
    .buttons { display:grid; gap:12px; margin:16px 0 20px; }
    .activity-row { display:grid; grid-template-columns:2fr 1fr; gap:8px; }
    .btn { font-size:1.25rem; padding:20px 16px; border-radius:14px; border:none; cursor:pointer; width:100%; min-height:72px; font-weight:700; }
    .btn:active { transform:scale(0.98); }
    .btn:disabled { opacity:0.5; cursor:not-allowed; }
    .study { background:#3b82f6; color:#fff; }
    .play { background:#22c55e; color:#fff; }
    .break { background:#f59e0b; color:#fff; }
    .study-stop { background:#2563eb; color:#fff; }
    .play-stop { background:#16a34a; color:#fff; }
    .break-stop { background:#d97706; color:#fff; }
    .kid { display:grid; grid-template-columns:1fr auto; grid-template-rows:auto auto; gap:8px 10px; align-items:center; }
    .kid label { grid-column:1 / -1; font-size:.95rem; opacity:.9; }
    .kid input { padding:10px 12px; border-radius:10px; border:1px solid #ccc; font-size:1rem; }
    .save { padding:10px 14px; border-radius:10px; border:none; background:#6b7280; color:#fff; font-weight:700; cursor:pointer; }
    .status { margin-top:22px; padding:14px 16px; border-radius:12px; background:rgba(125,125,125,.1); display:flex; gap:20px; justify-content:space-between; font-size:1.05rem; }
    .activity-totals { margin-top:12px; display:grid; gap:8px; }
    .activity-total { padding:10px 16px; border-radius:10px; font-size:1rem; font-weight:600; color:#fff; text-align:center; }
    .study-bg { background:#3b82f6; }
    .play-bg { background:#22c55e; }
    .break-bg { background:#f59e0b; }
    .weekly-monthly { margin-top:12px; padding:12px 16px; border:1px solid #d1d5db; border-radius:10px; background:#fafafa; }
    .period-total { font-size:0.9rem; padding:4px 0; color:#374151; }
    .note { margin-top:12px; font-size:.9rem; opacity:.8; }
  </style>
</head>
<body>
  <header class="wrap">
    <h1 id="title">これから何する？</h1>
  </header>
  <main class="wrap">
    <div class="buttons">
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

    <section class="status">
      <div id="now">今：—</div>
      <div id="today">今日：— 分</div>
    </section>

    <section class="activity-totals">
      <div id="study-total" class="activity-total study-bg">勉強：— 分</div>
      <div id="play-total" class="activity-total play-bg">遊び：— 分</div>
      <div id="break-total" class="activity-total break-bg">休憩：— 分</div>
    </section>

    <section class="weekly-monthly">
      <div id="week-total" class="period-total">【今週】勉強：— 分 ／ 遊び：— 分 ／ 休憩：— 分</div>
      <div id="month-total" class="period-total">【今月】勉強：— 分 ／ 遊び：— 分 ／ 休憩：— 分</div>
    </section>

    <div class="note" id="note">※ URLに ?kid=UUID を指定してください。</div>
  </main>

<script>
  const BASE = location.pathname.replace(/\/[^\/]*$/, ""); // /kid-activity-tracker
  const api = (p) => `${BASE}/api/${p}`;

  function getKidId() {
    const params = new URLSearchParams(location.search);
    return params.get('kid') || '';
  }
  async function startActivity(label) {
    const kid_id = getKidId();
    if (!kid_id) return alert("URLに ?kid=UUID を指定してください");
    try {
      const r = await fetch(api("switch.php"), {
        method:"POST",
        headers:{ "Content-Type":"application/json" },
        body: JSON.stringify({ kid_id, label })
      });
      const j = await r.json();
      if (!r.ok || !j.ok) throw new Error(j.error || "failed");
      await refresh();
      alert(`${jp(label)}を開始しました`);
    } catch (e) {
      alert("エラー: " + e.message);
    }
  }

  async function stopActivity() {
    const kid_id = getKidId();
    if (!kid_id) return alert("URLに ?kid=UUID を指定してください");
    try {
      const r = await fetch(api("stop.php"), {
        method:"POST",
        headers:{ "Content-Type":"application/json" },
        body: JSON.stringify({ kid_id })
      });
      const j = await r.json();
      if (!r.ok || !j.ok) throw new Error(j.error || "failed");
      await refresh();
      alert("終了しました");
    } catch (e) {
      alert("エラー: " + e.message);
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
        `今日：${Math.ceil((j.totals.today_sec||0)/60)} 分`;
      
      // 活動別累計時間を表示
      document.getElementById("study-total").textContent =
        `勉強：${Math.ceil((j.today_by_activity.study_sec||0)/60)} 分`;
      document.getElementById("play-total").textContent =
        `遊び：${Math.ceil((j.today_by_activity.play_sec||0)/60)} 分`;
      document.getElementById("break-total").textContent =
        `休憩：${Math.ceil((j.today_by_activity.break_sec||0)/60)} 分`;
      
      // 今週・今月の活動別累計時間を表示
      document.getElementById("week-total").textContent =
        `【今週】勉強：${Math.ceil((j.week_by_activity.study_sec||0)/60)} 分 ／ 遊び：${Math.ceil((j.week_by_activity.play_sec||0)/60)} 分 ／ 休憩：${Math.ceil((j.week_by_activity.break_sec||0)/60)} 分`;
      document.getElementById("month-total").textContent =
        `【今月】勉強：${Math.ceil((j.month_by_activity.study_sec||0)/60)} 分 ／ 遊び：${Math.ceil((j.month_by_activity.play_sec||0)/60)} 分 ／ 休憩：${Math.ceil((j.month_by_activity.break_sec||0)/60)} 分`;
      
      updateButtons(j.now.label || null);
    }
  }
  function jp(label){ return label==="study"?"勉強":label==="play"?"遊び":"休憩"; }
  function toJstHHmm(iso){
    const d = new Date(iso);
    const j = new Date(d.getTime() + 9*3600*1000);
    return String(j.getUTCHours()).padStart(2,"0")+":"+String(j.getUTCMinutes()).padStart(2,"0");
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
    }
    if ("serviceWorker" in navigator) navigator.serviceWorker.register(`${BASE}/pwa/service-worker.js`);
  }
  window.addEventListener("load", initPage);
</script>
</body>
</html>