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
  <link rel="stylesheet" href="/kid-activity-tracker/style.css">
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

    <!-- 親用画面 -->
    <div id="parent-screen" style="display:none;">
      <div id="all-kids-status"></div>
    </div>

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
      // 子ども用画面を表示
      showKidScreen();
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
      // 親用画面を表示
      showParentScreen();
    }
    if ("serviceWorker" in navigator) navigator.serviceWorker.register(`${BASE}/pwa/service-worker.js`);
  }

  function showKidScreen() {
    document.getElementById("kid-screen").style.display = "block";
    document.getElementById("kid-status").style.display = "flex";
    document.getElementById("kid-activity-totals").style.display = "grid";
    document.getElementById("kid-weekly-monthly").style.display = "block";
    document.getElementById("parent-screen").style.display = "none";
  }

  function showParentScreen() {
    document.getElementById("kid-screen").style.display = "none";
    document.getElementById("kid-status").style.display = "none";
    document.getElementById("kid-activity-totals").style.display = "none";
    document.getElementById("kid-weekly-monthly").style.display = "none";
    document.getElementById("parent-screen").style.display = "block";
    
    document.getElementById("title").textContent = "みんなの活動状況";
    document.getElementById("note").textContent = "※ 全員の活動を一覧表示しています。";
    
    loadAllKidsStatus();
  }

  async function loadAllKidsStatus() {
    try {
      const r = await fetch(api("all_stats.php"), { method:"GET" });
      const j = await r.json();
      if (j.ok) {
        displayAllKidsStatus(j.kids);
      }
    } catch (e) {
      console.error("全員の状況取得に失敗:", e);
      document.getElementById("all-kids-status").innerHTML = "<p>データの読み込みに失敗しました</p>";
    }
  }

  function displayAllKidsStatus(kids) {
    const container = document.getElementById("all-kids-status");
    let html = "";
    
    kids.forEach(kid => {
      const currentActivity = kid.now.label ? `今：${jp(kid.now.label)}（${toJstHHmm(kid.now.since)}開始）` : "今：休憩中";
      
      html += `
        <div class="kid-card">
          <div class="kid-name">${kid.kid_name}</div>
          <div class="kid-current">${currentActivity}</div>
          <div class="kid-stats">
            <div class="kid-stat">今日合計：${formatTime(kid.totals.today_sec||0)}</div>
            <div class="kid-stat">勉強：${formatTime(kid.today_by_activity.study_sec||0)} / 遊び：${formatTime(kid.today_by_activity.play_sec||0)} / 休憩：${formatTime(kid.today_by_activity.break_sec||0)}</div>
            <div class="kid-stat">【今週】勉強：${formatTime(kid.week_by_activity.study_sec||0)} / 遊び：${formatTime(kid.week_by_activity.play_sec||0)} / 休憩：${formatTime(kid.week_by_activity.break_sec||0)}</div>
            <div class="kid-stat">【今月】勉強：${formatTime(kid.month_by_activity.study_sec||0)} / 遊び：${formatTime(kid.month_by_activity.play_sec||0)} / 休憩：${formatTime(kid.month_by_activity.break_sec||0)}</div>
          </div>
        </div>
      `;
    });
    
    container.innerHTML = html;
  }
  window.addEventListener("load", initPage);
</script>
</body>
</html>