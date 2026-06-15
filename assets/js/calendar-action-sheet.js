(() => {
  const table=document.getElementById('calendarTable');
  const modal=document.getElementById('calendarEditModal');
  const sheet=document.getElementById('actionSheet');
  if(!table) return;

  const slotHeight=72, startHour=8;
  let state=null, selectedEvent=null, suppressClick=false;
  const indicator=document.createElement('div'); indicator.className='drop-indicator'; indicator.style.display='none';

  const getPoint=e=>{const p=e.touches?e.touches[0]:e;return{x:p.clientX,y:p.clientY}};
  const snapY=y=>Math.max(0,Math.round(y/slotHeight)*slotHeight);
  const yToTime=y=>{const slot=Math.max(0,Math.round(y/slotHeight));const min=startHour*60+slot*30;return`${String(Math.floor(min/60)).padStart(2,'0')}:${String(min%60).padStart(2,'0')}`};
  const plusMinutes=(time,mins)=>{const [h,m]=time.split(':').map(Number);const d=new Date(2000,0,1,h,m+mins);return`${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`};
  const staffColumnFromPoint=(x,y)=>{const el=document.elementFromPoint(x,y);return el?el.closest('.staff-column'):null};
  const closeSheet=()=>sheet.hidden=true;
  const closeModal=()=>modal.hidden=true;

  function openModal(mode, data){
    document.getElementById('editMode').value=mode;
    document.getElementById('editType').value=data.type || 'availability';
    document.getElementById('editId').value=data.id || '';
    document.getElementById('editStaffId').value=data.staffId || '';
    document.getElementById('editDate').value=data.date || table.dataset.date;
    document.getElementById('editStart').value=data.start || '10:00';
    document.getElementById('editEnd').value=data.end || plusMinutes(data.start || '10:00', 60);
    document.getElementById('editNote').value=data.note || '';
    document.getElementById('editCapacity').value=data.capacity || 1;

    const isAvailability=(data.type || 'availability')==='availability';
    document.getElementById('modalTitle').textContent=mode==='create'?'空き枠追加':(isAvailability?'空き枠編集':'予約時間編集');
    document.getElementById('modalEyebrow').textContent=mode==='create'?'CREATE SCHEDULE':'EDIT SCHEDULE';
    document.getElementById('modalSubmit').textContent=mode==='create'?'追加する':'保存する';
    document.getElementById('noteLabel').hidden=!isAvailability;
    document.getElementById('capacityLabel').hidden=!isAvailability;
    modal.hidden=false;
  }

  function openSheet(eventEl){
    selectedEvent=eventEl;
    const type=eventEl.dataset.type;
    document.getElementById('sheetTitle').textContent=type==='availability'?'空き枠':'予約';
    document.getElementById('sheetSubTitle').textContent=`${eventEl.dataset.start} - ${eventEl.dataset.end}`;
    document.getElementById('sheetDuplicate').hidden=type!=='availability';
    sheet.hidden=false;
  }

  async function api(path,payload){
    const res=await fetch(path,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
    const json=await res.json();
    if(!json.ok) throw new Error(json.error || '処理に失敗しました');
    return json;
  }

  function beginDrag(el,e){
    const p=getPoint(e), r=el.getBoundingClientRect();
    state={el,type:el.dataset.type,id:el.dataset.id,pointerOffsetY:p.y-r.top,originalTransition:el.style.transition||''};
    suppressClick=true; el.classList.add('dragging'); el.style.transition='none'; document.body.style.userSelect='none';
  }

  function moveDrag(e){
    if(!state) return; e.preventDefault();
    const p=getPoint(e), col=staffColumnFromPoint(p.x,p.y);
    document.querySelectorAll('.staff-column.drop-target').forEach(c=>c.classList.remove('drop-target'));
    if(!col){indicator.style.display='none';return}
    col.classList.add('drop-target'); if(indicator.parentElement!==col) col.appendChild(indicator);
    const r=col.getBoundingClientRect(), y=snapY(p.y-r.top+col.scrollTop-state.pointerOffsetY);
    indicator.style.top=`${y}px`; indicator.style.display='block'; state.dropColumn=col; state.dropY=y;
  }

  async function endDrag(){
    if(!state) return;
    const col=state.dropColumn, y=state.dropY;
    document.querySelectorAll('.staff-column.drop-target').forEach(c=>c.classList.remove('drop-target'));
    indicator.style.display='none'; document.body.style.userSelect='';
    state.el.classList.remove('dragging','drag-ready'); state.el.style.transition=state.originalTransition;
    if(!col || y===undefined){state=null; setTimeout(()=>suppressClick=false,300); return}
    try{
      await api('./api/calendar-move.php',{type:state.type,id:state.id,staff_id:col.dataset.staffId,date:table.dataset.date,time:yToTime(y)});
      location.reload();
    }catch(err){alert(err.message);location.reload()}
    state=null; setTimeout(()=>suppressClick=false,300);
  }

  document.querySelectorAll('.draggable-event').forEach(el=>{
    let timer=null, moved=false, start=null;
    const clear=()=>{if(timer)clearTimeout(timer);timer=null;el.classList.remove('drag-ready')};
    const down=e=>{start=getPoint(e);moved=false;clear();timer=setTimeout(()=>{el.classList.add('drag-ready');beginDrag(el,e)},450)};
    const move=e=>{const p=getPoint(e);if(start&&(Math.abs(p.x-start.x)>8||Math.abs(p.y-start.y)>8))moved=true;if(state&&state.el===el)moveDrag(e);else if(moved)clear()};
    const up=()=>{clear();if(state&&state.el===el)endDrag()};
    el.addEventListener('mousedown',down); window.addEventListener('mousemove',move); window.addEventListener('mouseup',up);
    el.addEventListener('touchstart',down,{passive:false}); window.addEventListener('touchmove',move,{passive:false}); window.addEventListener('touchend',up); window.addEventListener('touchcancel',up);
    el.addEventListener('click',e=>{e.stopPropagation();if(suppressClick)return;openSheet(el)});
  });

  document.querySelectorAll('.staff-column').forEach(col=>{
    col.addEventListener('click',e=>{
      if(e.target.closest('.calendar-event') || suppressClick) return;
      const rect=col.getBoundingClientRect();
      const y=snapY(e.clientY-rect.top+col.scrollTop);
      const start=yToTime(y);
      openModal('create',{type:'availability',staffId:col.dataset.staffId,date:table.dataset.date,start,end:plusMinutes(start,60),capacity:1,note:''});
    });
  });

  document.getElementById('sheetCancel').addEventListener('click',closeSheet);
  sheet.addEventListener('click',e=>{if(e.target===sheet)closeSheet()});
  document.getElementById('sheetEdit').addEventListener('click',()=>{if(!selectedEvent)return;closeSheet();openModal('edit',{type:selectedEvent.dataset.type,id:selectedEvent.dataset.id,date:selectedEvent.dataset.date,start:selectedEvent.dataset.start,end:selectedEvent.dataset.end,note:selectedEvent.dataset.note,capacity:selectedEvent.dataset.capacity})});
  document.getElementById('sheetCreate').addEventListener('click',()=>{if(!selectedEvent)return;const col=selectedEvent.closest('.staff-column');closeSheet();openModal('create',{type:'availability',staffId:col.dataset.staffId,date:selectedEvent.dataset.date,start:selectedEvent.dataset.start,end:selectedEvent.dataset.end,capacity:1,note:''})});
  document.getElementById('sheetDuplicate').addEventListener('click',async()=>{if(!selectedEvent)return;try{await api('./api/calendar-duplicate.php',{type:selectedEvent.dataset.type,id:selectedEvent.dataset.id});location.reload()}catch(err){alert(err.message)}});
  document.getElementById('sheetDelete').addEventListener('click',async()=>{if(!selectedEvent)return;const label=selectedEvent.dataset.type==='availability'?'この空き枠':'この予約';if(!confirm(`${label}を削除しますか？`))return;try{await api('./api/calendar-delete.php',{type:selectedEvent.dataset.type,id:selectedEvent.dataset.id});location.reload()}catch(err){alert(err.message)}});

  document.getElementById('modalClose').addEventListener('click',closeModal);
  modal.addEventListener('click',e=>{if(e.target===modal)closeModal()});
  document.getElementById('calendarEditForm').addEventListener('submit',async e=>{
    e.preventDefault();
    const mode=document.getElementById('editMode').value;
    const payload={
      type:document.getElementById('editType').value,
      id:document.getElementById('editId').value,
      staff_id:document.getElementById('editStaffId').value,
      date:document.getElementById('editDate').value,
      start_time:document.getElementById('editStart').value,
      end_time:document.getElementById('editEnd').value,
      capacity:document.getElementById('editCapacity').value,
      note:document.getElementById('editNote').value
    };
    try{await api(mode==='create'?'./api/calendar-create.php':'./api/calendar-update.php',payload);location.reload()}catch(err){alert(err.message)}
  });
})();
