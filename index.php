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
    document.getElementById("kid-screen").style.display = "grid";
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
    
    // QRグリッド用のセクションを動的に追加
    const noteElement = document.getElementById("note");
    const qrSection = document.createElement("section");
    qrSection.id = "kid-qr-grid";
    qrSection.className = "qr-grid";
    noteElement.parentNode.insertBefore(qrSection, noteElement.nextSibling);
    
    // CSS・JSを動的に追加
    if (!document.querySelector('link[href*="qr-grid.css"]')) {
      const cssLink = document.createElement("link");
      cssLink.rel = "stylesheet";
      cssLink.href = "/kid-activity-tracker/assets/qr-grid.css";
      document.head.appendChild(cssLink);
    }
    
    if (!document.querySelector('script[src*="qrcode.min.js"]')) {
      const qrScript = document.createElement("script");
      qrScript.src = "/kid-activity-tracker/assets/qrcode.min.js";
      document.head.appendChild(qrScript);
    }
    
    if (!document.querySelector('script[src*="qr-grid.js"]')) {
      const gridScript = document.createElement("script");
      gridScript.src = "/kid-activity-tracker/assets/qr-grid.js";
      gridScript.onload = () => {
        window.qrGridLoaded = true;
      };
      document.head.appendChild(gridScript);
    }
    
    loadAllKidsStatus();
  }

  async function loadAllKidsStatus() {
    try {
      const r = await fetch(api("all_stats.php"), { method:"GET" });
      const j = await r.json();
      if (j.ok) {
        displayAllKidsStatus(j.kids);
        displayKidsQRGrid(j.kids);
      }
    } catch (e) {
      console.error("全員の状況取得に失敗:", e);
      document.getElementById("all-kids-status").innerHTML = "<p>データの読み込みに失敗しました</p>";
    }
  }

  function displayAllKidsStatus(kids) {
    const container = document.getElementById("all-kids-status");
    let html = "";
    
    // 累計情報セクション
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
    
    // ログセクション
    kids.forEach(kid => {
      html += `
        <div class="activity-logs">
          <div class="logs-header">
            <div class="logs-title">${kid.kid_name}の活動ログ</div>
            <button class="logs-toggle" onclick="toggleLogs('${kid.kid_id}')" id="toggle-${kid.kid_id}">本日全件表示</button>
          </div>
          <div class="log-list" id="logs-${kid.kid_id}">
            <div class="log-entry">読み込み中...</div>
          </div>
        </div>
      `;
    });
    
    container.innerHTML = html;
    
    // 各子供のログを読み込み
    kids.forEach(kid => {
      loadActivityLogs(kid.kid_id, false);
    });
  }

  async function loadActivityLogs(kid_id, today_only = false) {
    try {
      const params = new URLSearchParams({
        kid_id: kid_id,
        today_only: today_only.toString(),
        limit: '20'
      });
      
      const r = await fetch(api(`logs.php?${params}`), { method: "GET" });
      const j = await r.json();
      
      if (j.ok) {
        displayActivityLogs(kid_id, j.events, j.today_event_count, today_only);
        updateToggleButton(kid_id, today_only, j.today_event_count);
      } else {
        document.getElementById(`logs-${kid_id}`).innerHTML = '<div class="log-entry">ログの読み込みに失敗しました</div>';
      }
    } catch (e) {
      console.error("ログ取得エラー:", e);
      document.getElementById(`logs-${kid_id}`).innerHTML = '<div class="log-entry">ログの読み込みに失敗しました</div>';
    }
  }

  function displayActivityLogs(kid_id, events, today_event_count, showing_today_only) {
    const container = document.getElementById(`logs-${kid_id}`);
    
    if (events.length === 0) {
      container.innerHTML = '<div class="log-entry">ログがありません</div>';
      return;
    }
    
    let html = '';
    let displayedCount = 0;
    
    events.forEach((event, index) => {
      const isHidden = !showing_today_only && index >= 20;
      const hiddenClass = isHidden ? ' style="display:none" class="hidden-log"' : '';
      
      html += `
        <div class="log-entry log-${event.type} log-${event.label}"${hiddenClass}>
          <span class="log-action">
            ${jp(event.label)}を${event.type === 'start' ? '開始' : '終了'}
          </span>
          <span class="log-time">${event.display_time}</span>
        </div>
      `;
      
      if (!isHidden) displayedCount++;
    });
    
    container.innerHTML = html;
  }

  function updateToggleButton(kid_id, showing_today_only, today_event_count) {
    const toggle = document.getElementById(`toggle-${kid_id}`);
    if (showing_today_only) {
      toggle.textContent = '最新20件表示';
      toggle.classList.add('active');
    } else {
      const hiddenCount = Math.max(0, today_event_count - 20);
      if (hiddenCount > 0) {
        toggle.textContent = `本日全件表示 (+${hiddenCount}件)`;
      } else {
        toggle.textContent = '本日全件表示';
      }
      toggle.classList.remove('active');
    }
  }

  function toggleLogs(kid_id) {
    const toggle = document.getElementById(`toggle-${kid_id}`);
    const isShowingToday = toggle.classList.contains('active');
    
    // 現在が「本日全件表示」なら「最新20件」に、逆なら「本日全件表示」に切り替え
    loadActivityLogs(kid_id, !isShowingToday);
  }

  function displayKidsQRGrid(kids) {
    const qrSection = document.getElementById("kid-qr-grid");
    if (!qrSection || kids.length === 0) {
      if (qrSection) {
        qrSection.innerHTML = '<div class="qr-notice">子どもが登録されていません</div>';
      }
      return;
    }

    let html = '';
    kids.forEach(kid => {
      const name = kid.kid_name || '';
      const id = kid.kid_id || '';
      const url = `https://netservice.jp/kid-activity-tracker/?kid_id=${encodeURIComponent(id)}`;
      
      html += `
        <div class="qr-card">
          <div class="qr-name">${escapeHtml(name)}</div>
          <div class="qr-canvas" data-url="${escapeHtml(url)}"></div>
          <button class="qr-copy" type="button" aria-label="${escapeHtml(name)}のリンクをコピー" data-copy="${escapeHtml(url)}">リンクをコピー</button>
        </div>
      `;
    });
    
    qrSection.innerHTML = html;
    
    // QRコード生成を実行（スクリプト読み込み後）
    if (window.qrGridLoaded && window.initQRGrid) {
      window.initQRGrid();
    } else {
      // スクリプトの読み込み待機
      const checkQRGrid = setInterval(() => {
        if (window.qrGridLoaded && window.initQRGrid) {
          window.initQRGrid();
          clearInterval(checkQRGrid);
        }
      }, 100);
      
      // タイムアウト処理
      setTimeout(() => clearInterval(checkQRGrid), 5000);
    }
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  window.addEventListener("load", initPage);
</script>
</body>
</html>