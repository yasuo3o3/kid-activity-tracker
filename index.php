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
    .btn { font-size:1.25rem; padding:20px 16px; border-radius:14px; border:none; cursor:pointer; width:100%; min-height:72px; font-weight:700; }
    .btn:active { transform:scale(0.98); }
    .study { background:#3b82f6; color:#fff; }
    .play { background:#22c55e; color:#fff; }
    .break { background:#f59e0b; color:#fff; }
    .kid { display:grid; grid-template-columns:1fr auto; grid-template-rows:auto auto; gap:8px 10px; align-items:center; }
    .kid label { grid-column:1 / -1; font-size:.95rem; opacity:.9; }
    .kid input { padding:10px 12px; border-radius:10px; border:1px solid #ccc; font-size:1rem; }
    .save { padding:10px 14px; border-radius:10px; border:none; background:#6b7280; color:#fff; font-weight:700; cursor:pointer; }
    .status { margin-top:22px; padding:14px 16px; border-radius:12px; background:rgba(125,125,125,.1); display:flex; gap:20px; justify-content:space-between; font-size:1.05rem; }
    .note { margin-top:12px; font-size:.9rem; opacity:.8; }
  </style>
</head>
<body>
  <header class="wrap">
    <h1>これから何する？</h1>
  </header>
  <main class="wrap">
    <div class="buttons">
      <button class="btn study" onclick="send('study')">勉強する</button>
      <button class="btn play"  onclick="send('play')">遊ぶ</button>
      <button class="btn break" onclick="send('break')">休憩する</button>
    </div>

    <div class="kid">
      <label for="kid">kid_id（UUID）</label>
      <input id="kid" type="text" placeholder="例: 121adf20-..." />
      <button class="save" onclick="saveKid()">保存</button>
    </div>

    <section class="status">
      <div id="now">今：—</div>
      <div id="today">今日：— 分</div>
    </section>

    <div class="note">※ まず kid_id を入力して保存 → ボタンを押すと記録されます。</div>
  </main>

<script>
  const BASE = location.pathname.replace(/\/[^\/]*$/, ""); // /kid-activity-tracker
  const api = (p) => `${BASE}/api/${p}`;

  function loadKid() {
    const v = localStorage.getItem("kid_id");
    const input = document.getElementById("kid");
    if (v) input.value = v;
    return v || "";
  }
  function saveKid() {
    const v = document.getElementById("kid").value.trim();
    if (!v) return alert("kid_id を入力してください");
    localStorage.setItem("kid_id", v);
    alert("保存しました");
  }
  async function send(label) {
    const kid_id = loadKid();
    if (!kid_id) return alert("kid_id を先に保存してください");
    try {
      const r = await fetch(api("switch.php"), {
        method:"POST",
        headers:{ "Content-Type":"application/json" },
        body: JSON.stringify({ kid_id, label })
      });
      const j = await r.json();
      if (!r.ok || !j.ok) throw new Error(j.error || "failed");
      await refresh();
      alert("記録しました");
    } catch (e) {
      alert("エラー: " + e.message);
    }
  }
  async function refresh() {
    const kid_id = loadKid();
    if (!kid_id) return;
    const r = await fetch(api(`stats.php?kid_id=${encodeURIComponent(kid_id)}`), { method:"GET" });
    const j = await r.json();
    if (j.ok) {
      document.getElementById("now").textContent =
        j.now.label ? `今：${jp(j.now.label)}（${toJstHHmm(j.now.since)}開始）` : "今：—";
      document.getElementById("today").textContent =
        `今日：${Math.floor((j.totals.today_sec||0)/60)} 分`;
    }
  }
  function jp(label){ return label==="study"?"勉強":label==="play"?"遊び":"休憩"; }
  function toJstHHmm(iso){
    const d = new Date(iso);
    const j = new Date(d.getTime() + 9*3600*1000);
    return String(j.getUTCHours()).padStart(2,"0")+":"+String(j.getUTCMinutes()).padStart(2,"0");
  }
  window.addEventListener("load", () => { loadKid(); refresh(); if ("serviceWorker" in navigator) navigator.serviceWorker.register(`${BASE}/pwa/service-worker.js`); });
</script>
</body>
</html>