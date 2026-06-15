<?php
$currentFile = basename($_SERVER['SCRIPT_NAME'] ?? '');

function onme_side_active(array $files, string $currentFile): string {
  return in_array($currentFile, $files, true) ? ' is-active' : '';
}
?>
<style>
/* ON;ME OS Japanese unified sidebar */
html,body{
  min-width:1200px!important;
}
.onme-fixed-sidebar{
  position:fixed!important;
  top:0!important;
  left:0!important;
  bottom:0!important;
  width:210px!important;
  min-width:210px!important;
  max-width:210px!important;
  padding:20px 14px!important;
  background:rgba(3,3,4,.94)!important;
  border-right:1px solid rgba(255,255,255,.08)!important;
  backdrop-filter:blur(22px)!important;
  overflow-y:auto!important;
  z-index:9999!important;
  box-sizing:border-box!important;
}
body .pro-main,
body main.pro-main{
  margin-left:210px!important;
  width:calc(100vw - 210px)!important;
  max-width:none!important;
  min-width:980px!important;
  padding:32px 42px!important;
  box-sizing:border-box!important;
  display:block!important;
}
body .pro-main *,
body main.pro-main *{
  writing-mode:horizontal-tb!important;
  text-orientation:mixed!important;
  word-break:normal!important;
  overflow-wrap:normal!important;
}
body .pro-top{
  width:100%!important;
  display:flex!important;
  align-items:flex-start!important;
  justify-content:space-between!important;
  gap:24px!important;
  margin-bottom:24px!important;
}
body .pro-top h1,
body .pro-main h1{
  display:block!important;
  max-width:none!important;
  width:auto!important;
  white-space:normal!important;
  font-size:clamp(56px,5vw,92px)!important;
  line-height:.95!important;
  letter-spacing:-.08em!important;
  margin:0!important;
}
body .pro-actions{
  display:flex!important;
  gap:10px!important;
  align-items:center!important;
  flex-wrap:wrap!important;
}
body .pos-kpi,
body .sales-kpi,
body .register-status,
body .register-kpi,
body .detail-kpi,
body .payment-detail,
body .cash-detail,
body .gs-kpi{
  width:100%!important;
  display:grid!important;
  grid-template-columns:repeat(4,minmax(0,1fr))!important;
  gap:14px!important;
}
body .register-grid{
  width:100%!important;
  display:grid!important;
  grid-template-columns:minmax(420px,1.1fr) minmax(330px,.8fr) minmax(340px,.85fr)!important;
  gap:18px!important;
  align-items:start!important;
}
body .sales-layout,
body .register-layout,
body .gs-layout{
  width:100%!important;
  display:grid!important;
  grid-template-columns:1.15fr .85fr!important;
  gap:18px!important;
}
.onme-fixed-brand{
  color:#fff!important;
  margin-bottom:24px!important;
}
.onme-fixed-brand .mini{
  display:block!important;
  font-size:10px!important;
  letter-spacing:.25em!important;
  font-weight:900!important;
  color:rgba(255,255,255,.62)!important;
  margin-bottom:10px!important;
}
.onme-fixed-brand .logo{
  display:block!important;
  font-size:34px!important;
  line-height:1!important;
  letter-spacing:-.09em!important;
  font-weight:950!important;
}
.onme-fixed-nav{
  display:grid!important;
  gap:6px!important;
}
.onme-fixed-nav small{
  display:block!important;
  margin:12px 10px 4px!important;
  color:rgba(255,255,255,.34)!important;
  font-size:9px!important;
  letter-spacing:.22em!important;
  font-weight:950!important;
}
.onme-fixed-nav a{
  display:flex!important;
  align-items:center!important;
  gap:8px!important;
  min-height:38px!important;
  padding:9px 13px!important;
  border-radius:14px!important;
  color:rgba(255,255,255,.72)!important;
  text-decoration:none!important;
  font-size:14px!important;
  font-weight:950!important;
  line-height:1.1!important;
  white-space:nowrap!important;
}
.onme-fixed-nav a .icon{
  width:18px!important;
  display:inline-grid!important;
  place-items:center!important;
  font-size:14px!important;
  opacity:.95!important;
}
.onme-fixed-nav a:hover{
  background:rgba(255,255,255,.10)!important;
  color:#fff!important;
}
.onme-fixed-nav a.is-active{
  background:#fff!important;
  color:#050505!important;
}
@media(max-width:980px){
  html,body{min-width:0!important;}
  .onme-fixed-sidebar{
    position:relative!important;
    width:auto!important;
    min-width:0!important;
    max-width:none!important;
    bottom:auto!important;
    border-right:0!important;
    border-bottom:1px solid rgba(255,255,255,.08)!important;
  }
  body .pro-main,
  body main.pro-main{
    margin-left:0!important;
    width:100%!important;
    min-width:0!important;
    padding:24px!important;
  }
}
</style>

<aside class="onme-fixed-sidebar">
  <div class="onme-fixed-brand">
    <span class="mini">ON;ME OS</span>
    <span class="logo">ON;ME</span>
  </div>

  <nav class="onme-fixed-nav">
    <small>MAIN</small>
    <a class="<?= onme_side_active(['dashboard-v2.php','dashboard.php','index.php'], $currentFile) ?>" href="./dashboard-v2.php"><span class="icon">🏠</span>ダッシュボード</a>
    <a class="<?= onme_side_active(['calendar-master.php','calendar.php'], $currentFile) ?>" href="./calendar-master.php"><span class="icon">📅</span>カレンダー</a>
    <a class="<?= onme_side_active(['carte-master.php','customer-360.php'], $currentFile) ?>" href="./carte-master.php"><span class="icon">👤</span>お客様名簿</a>
    <a class="<?= onme_side_active(['analytics-pro.php'], $currentFile) ?>" href="./analytics-pro.php"><span class="icon">📊</span>分析データ</a>

    <small>SALES</small>
    <a class="<?= onme_side_active(['pos-pro.php'], $currentFile) ?>" href="./pos-pro.php"><span class="icon">💳</span>POS</a>
    <a class="<?= onme_side_active(['sales-history.php'], $currentFile) ?>" href="./sales-history.php"><span class="icon">🧾</span>過去売上</a>
    <a class="<?= onme_side_active(['register-close.php'], $currentFile) ?>" href="./register-close.php"><span class="icon">💰</span>レジ締め</a>
    <a class="<?= onme_side_active(['subscription-pro.php'], $currentFile) ?>" href="./subscription-pro.php"><span class="icon">💎</span>サブスク</a>

    <small>MARKETING</small>
    <a class="<?= onme_side_active(['crm-pro.php'], $currentFile) ?>" href="./crm-pro.php"><span class="icon">💬</span>LINE</a>

    <small>OPERATIONS</small>
    <a class="<?= onme_side_active(['menu-master.php','menus.php'], $currentFile) ?>" href="./menu-master.php"><span class="icon">📝</span>メニュー登録</a>
    <a class="<?= onme_side_active(['staff.php','staff-shift.php'], $currentFile) ?>" href="./staff.php"><span class="icon">👥</span>スタッフ登録</a>
    <a class="<?= onme_side_active(['consent-pro.php','consent-sign.php'], $currentFile) ?>" href="./consent-pro.php"><span class="icon">✍️</span>お客様同意書</a>

    <small>SYSTEM</small>
    <a class="<?= onme_side_active(['google-sync.php'], $currentFile) ?>" href="./google-sync.php"><span class="icon">🔗</span>Google</a>
    <a class="<?= onme_side_active(['settings-pro.php'], $currentFile) ?>" href="./settings-pro.php"><span class="icon">⚙️</span>設定</a>
    <a href="./logout.php"><span class="icon">🚪</span>ログアウト</a>
  </nav>
</aside>

<script src="../assets/js/onme-japanese-labels.js?v=1" defer></script>
