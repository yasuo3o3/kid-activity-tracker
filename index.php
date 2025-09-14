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
  <link rel="manifest" href="./pwa/manifest.json" id="manifest-link">
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
<script src="./assets/copy-link.js?v=2"></script>
<script>
  // シンプルなURL生成関数（キャッシュ問題回避）
  function simpleUrl(path) {
    const base = location.origin + location.pathname.replace(/\/[^\/]*$/, '/');
    return base + path;
  }
  const api = (p) => simpleUrl(`api/${p}`);

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
    return params.get('kid') || params.get('child') || '';
  }
  async function startActivity(label) {
    const kid_id = getKidId();
    if (!kid_id) return notify.error("URLに ?kid=UUID を指定してください");
    try {
      const apiUrl = api("switch.php");
      console.log("Switch API URL:", apiUrl);
      const r = await fetch(apiUrl, {
        method:"POST",
        headers:{ "Content-Type":"application/json" },
        body: JSON.stringify({ kid_id, label })
      });
      console.log("Switch Response status:", r.status);
      const j = await r.json();
      console.log("Switch Response data:", j);
      if (!r.ok || !j.ok) throw new Error(j.error || "failed");
      await refresh();
      notify.success(`${jp(label)}を開始しました`);
      // ボタン状態を即座に更新
      updateButtons(label);
    } catch (e) {
      console.error("Switch error:", e);
      notify.error("エラー: " + e.message);
    }
  }

  async function stopActivity() {
    const kid_id = getKidId();
    if (!kid_id) return notify.error("URLに ?kid=UUID を指定してください");
    try {
      const apiUrl = api("stop.php");
      console.log("Stop API URL:", apiUrl);
      const r = await fetch(apiUrl, {
        method:"POST",
        headers:{ "Content-Type":"application/json" },
        body: JSON.stringify({ kid_id })
      });
      console.log("Stop Response status:", r.status);
      const j = await r.json();
      console.log("Stop Response data:", j);
      if (!r.ok || !j.ok) {
        if (j.error === 'no active session') {
          notify.info("現在アクティブな活動がありません");
        } else {
          throw new Error(j.error || "failed");
        }
      } else {
        await refresh();
        notify.success("終了しました");
        // 全ボタンを非アクティブ状態に
        updateButtons(null);
      }
    } catch (e) {
      notify.error("エラー: " + e.message);
    }
  }
  function updateButtons(currentLabel) {
    console.log("updateButtons called with:", currentLabel);
    const activities = ['study', 'play', 'break'];
    activities.forEach(activity => {
      const startBtn = document.getElementById(`${activity}-btn`);
      const stopBtn = document.getElementById(`${activity}-stop`);
      
      if (!startBtn || !stopBtn) {
        console.warn(`Buttons not found for activity: ${activity}`);
        return;
      }
      
      if (currentLabel === activity) {
        console.log(`Setting ${activity} as active`);
        startBtn.disabled = true;
        stopBtn.disabled = false;
        startBtn.style.opacity = '0.5';
        stopBtn.style.opacity = '1';
      } else {
        console.log(`Setting ${activity} as inactive`);
        startBtn.disabled = false;
        stopBtn.disabled = true;
        startBtn.style.opacity = '1';
        stopBtn.style.opacity = '0.5';
      }
    });
  }

  async function refresh() {
    const kid_id = getKidId();
    if (!kid_id) return;
    const apiUrl = api(`stats.php?kid_id=${encodeURIComponent(kid_id)}`);
    console.log("Stats API URL:", apiUrl);
    const r = await fetch(apiUrl, { method:"GET" });
    console.log("Stats Response status:", r.status);
    const j = await r.json();
    console.log("Stats Response data:", j);
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
      
      // ボタンの状態を更新
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
  function getChildParam() {
    const params = new URLSearchParams(location.search);
    return params.get('child') || '';
  }

  function routeStartup() {
    const isPWA = window.matchMedia && window.matchMedia('(display-mode: standalone)').matches;
    const childParam = getChildParam();
    const storedChild = localStorage.getItem('selectedChild');

    console.log(`SPA startup: PWA=${isPWA}, child=${childParam}, stored=${storedChild}`);

    let targetChild = '';

    if (childParam) {
      targetChild = childParam;
      localStorage.setItem('selectedChild', childParam);
    } else if (storedChild) {
      targetChild = storedChild;
      goToChild(storedChild);
      return;
    }

    if (targetChild) {
      showChildScreen(targetChild);
    } else {
      showChildSelection();
    }
  }

  function goToChild(childId) {
    const url = new URL(location.href);
    url.searchParams.set('child', childId);
    localStorage.setItem('selectedChild', childId);
    history.replaceState(null, '', url.toString());
    showChildScreen(childId);
  }

  async function showChildScreen(childId) {
    try {
      const apiUrl = api(`stats.php?kid_id=${encodeURIComponent(childId)}`);
      console.log("Name fetch API URL:", apiUrl);
      const r = await fetch(apiUrl, { method:"GET" });
      console.log("Name fetch Response status:", r.status);
      const j = await r.json();
      console.log("Name fetch Response data:", j);
      if (j.ok && j.kid_name) {
        document.getElementById("title").textContent = `${j.kid_name}：これから何する？`;
        document.getElementById("note").textContent = `※ ${j.kid_name}専用画面です。`;

        // Update manifest for child-specific PWA
        updateManifestForChild(childId, j.kid_name);
      }
      refresh();
    } catch (e) {
      console.error("名前の取得に失敗:", e);
    }
  }

  function updateManifestForChild(childId, childName) {
    const manifestLink = document.getElementById('manifest-link');
    if (manifestLink) {
      const manifestUrl = `./pwa/manifest.php?child=${encodeURIComponent(childId)}&name=${encodeURIComponent(childName)}`;
      manifestLink.href = manifestUrl;
      console.log(`Updated manifest for child: ${childName} (${childId})`);
    }
  }

  function showChildSelection() {
    document.getElementById("title").textContent = "URLパラメータが必要です";
    document.getElementById("note").innerHTML = `※ URLに ?child=UUID を指定してください。<br>管理画面は <a href="admin.php">こちら</a> からアクセスできます。`;
    document.getElementById("kid-screen").style.display = "none";
    document.getElementById("kid-status").style.display = "none";
    document.getElementById("kid-activity-totals").style.display = "none";
    document.getElementById("kid-weekly-monthly").style.display = "none";
  }

  async function initPage() {
    routeStartup();
    if ("serviceWorker" in navigator) navigator.serviceWorker.register(simpleUrl("pwa/service-worker.js"));
  }



  document.addEventListener("DOMContentLoaded", initPage);
</script>

</body>
</html>