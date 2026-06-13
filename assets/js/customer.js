const state={menu:null,staff:null,slot:null,profile:null};

async function initLiff(){
  try{
    if(window.ONME_LIFF_ID && window.liff){
      await liff.init({liffId:window.ONME_LIFF_ID});
      if(liff.isLoggedIn()) state.profile=await liff.getProfile();
    }
  }catch(e){console.log('LIFF skipped',e)}
}
function showStep(n){document.querySelectorAll('.step').forEach(s=>s.classList.toggle('active',s.dataset.step==n))}
document.querySelectorAll('.menu-choice').forEach(btn=>btn.onclick=()=>{
  state.menu={id:btn.dataset.id,name:btn.dataset.name,minutes:Number(btn.dataset.minutes),price:Number(btn.dataset.price)};
  showStep(2);
});
document.querySelectorAll('.staff-choice').forEach(btn=>btn.onclick=async()=>{
  state.staff={id:btn.dataset.id,name:btn.dataset.name};
  await loadSlots();
  showStep(3);
});
document.querySelectorAll('.back').forEach(btn=>btn.onclick=()=>showStep(btn.dataset.back));
async function loadSlots(){
  const box=document.getElementById('slots'); box.innerHTML='読み込み中...';
  const url=`/api/slots.php?staff=${encodeURIComponent(state.staff.id)}&minutes=${state.menu.minutes}`;
  const res=await fetch(url); const data=await res.json();
  box.innerHTML='';
  if(!data.slots.length){box.innerHTML='<p class="lead">現在予約可能な枠がありません。</p>';return;}
  data.slots.forEach(s=>{
    const b=document.createElement('button');
    b.className='slot'; b.textContent=`${s.date} ${s.start}〜${s.end}`;
    b.onclick=()=>{state.slot=s; renderConfirm(); showStep(4)};
    box.appendChild(b);
  });
}
function renderConfirm(){
  document.getElementById('confirmBox').innerHTML=`
    <b>メニュー</b>：${state.menu.name}<br>
    <b>担当</b>：${state.staff.name}<br>
    <b>日時</b>：${state.slot.date} ${state.slot.start}〜${state.slot.end}<br>
    <b>料金</b>：¥${state.menu.price.toLocaleString()}
  `;
}
document.getElementById('submitReserve').onclick=async()=>{
  const name=document.getElementById('customerName').value.trim();
  const phone=document.getElementById('customerPhone').value.trim();
  if(!name||!phone){alert('お名前と電話番号を入力してください');return;}
  const res=await fetch('/api/reserve.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({...state,name,phone,lineProfile:state.profile})});
  const data=await res.json();
  if(data.ok){alert('予約が完了しました'); if(window.liff&&liff.isInClient()) liff.closeWindow();} else alert(data.message||'予約できませんでした');
};
initLiff();
