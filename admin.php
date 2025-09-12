<?php
// 親用管理画面
?><!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Kid Activity Tracker - 管理画面</title>
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
    <h1 id="title">みんなの活動状況</h1>
  </header>
  <main class="wrap">
    <!-- 親用画面 -->
    <div id="parent-screen">
      <div id="all-kids-status"></div>
    </div>

    <div class="note" id="note">※ 全員の活動を一覧表示しています。</div>
    
    <!-- リンク一覧セクション -->
    <section id="kid-link-grid" class="link-grid"></section>
  </main>
<script src="./assets/url-helper.js?v=3"></script>
<script src="./assets/copy-link.js?v=2"></script>
<script>
  // publicUrl() でAPI URLを生成
  const api = (p) => publicUrl(`api/${p}`);

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
  
  function jp(label){ return label==="study"?"勉強":label==="play"?"遊び":"休憩"; }
  
  function toJstHHmm(iso){
    const d = new Date(iso);
    const j = new Date(d.getTime() + 9*3600*1000);
    return String(j.getUTCHours()).padStart(2,"0")+":"+String(j.getUTCMinutes()).padStart(2,"0");
  }

  async function loadAllKidsStatus() {
    try {
      const apiUrl = api("all_stats.php");
      console.log("API URL:", apiUrl);
      const r = await fetch(apiUrl, { method:"GET" });
      console.log("Response status:", r.status);
      console.log("Response headers:", Object.fromEntries(r.headers.entries()));
      const j = await r.json();
      console.log("Response data:", j);
      if (j.ok) {
        displayAllKidsStatus(j.kids);
        displayKidsLinkGrid(j.kids);
      } else {
        document.getElementById("all-kids-status").innerHTML = "<p>APIエラー: " + (j.error || "不明なエラー") + "</p>";
      }
    } catch (e) {
      console.error("全員の状況取得に失敗:", e);
      document.getElementById("all-kids-status").innerHTML = "<p>データの読み込みに失敗しました: " + e.message + "</p>";
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
        const errorContainer = document.getElementById(`logs-${kid_id}`);
        if (errorContainer) errorContainer.innerHTML = '<div class="log-entry">ログの読み込みに失敗しました</div>';
      }
    } catch (e) {
      console.error("ログ取得エラー:", e);
      const errorContainer = document.getElementById(`logs-${kid_id}`);
      if (errorContainer) errorContainer.innerHTML = '<div class="log-entry">ログの読み込みに失敗しました</div>';
    }
  }

  function displayActivityLogs(kid_id, events, today_event_count, showing_today_only) {
    const container = document.getElementById(`logs-${kid_id}`);
    if (!container) {
      console.warn(`Element with id "logs-${kid_id}" not found`);
      return;
    }
    
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

  function displayKidsLinkGrid(kids) {
    const linkSection = document.getElementById("kid-link-grid");
    if (!linkSection || kids.length === 0) {
      if (linkSection) {
        linkSection.innerHTML = '<div class="link-notice">子どもが登録されていません</div>';
      }
      return;
    }

    let html = '';
    kids.forEach(kid => {
      const name = kid.kid_name || '';
      const id = kid.kid_id || '';
      const url = getKidUrl(id);
      
      html += `
        <div class="link-card">
          <div class="link-name">${escapeHtml(name)}</div>
          <button class="qr-copy" type="button" aria-label="${escapeHtml(name)}のリンクをコピー" data-copy="${escapeHtml(url)}">リンクをコピー</button>
        </div>
      `;
    });
    
    linkSection.innerHTML = html;
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  async function initPage() {
    loadAllKidsStatus();
    if ("serviceWorker" in navigator) navigator.serviceWorker.register(publicUrl("pwa/service-worker.js"));
  }

  document.addEventListener("DOMContentLoaded", initPage);
</script>

</body>
</html>