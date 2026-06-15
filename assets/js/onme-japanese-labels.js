(() => {
  const map = new Map([
    ['Dashboard', 'ダッシュボード'],
    ['Calendar Master', 'カレンダー'],
    ['Carte Master', 'お客様名簿'],
    ['Customers', 'お客様名簿'],
    ['Analytics', '分析データ'],
    ['Subscription', 'サブスク'],
    ['Menus', 'メニュー登録'],
    ['Menu Master', 'メニュー登録'],
    ['Staff', 'スタッフ登録'],
    ['Staff / Shift', 'スタッフ登録'],
    ['Consent', 'お客様同意書'],
    ['Google Sync', 'Google'],
    ['Settings', '設定'],
    ['Logout', 'ログアウト'],
    ['Quick Actions', 'クイック操作'],
    ['Today Sales', '本日会計'],
    ['Monthly Sales', '売上推移'],
    ['Staff Top', 'スタッフランキング'],
    ['Menu Top', 'メニュー別売上'],
    ['REGISTER CLOSE', 'レジ締め'],
    ['CHECKOUT', 'POSレジ'],
    ['BUSINESS ANALYTICS', '分析データ'],
    ['CUSTOMER CARTE', 'お客様名簿'],
    ['CUSTOMER CREATE', '顧客登録'],
    ['REALTIME SALON BI', 'ON;ME Salon BI'],
  ]);

  const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
  const nodes = [];
  while (walker.nextNode()) nodes.push(walker.currentNode);

  nodes.forEach((node) => {
    const raw = node.nodeValue || '';
    const trimmed = raw.trim();
    if (map.has(trimmed)) {
      node.nodeValue = raw.replace(trimmed, map.get(trimmed));
    }
  });
})();
