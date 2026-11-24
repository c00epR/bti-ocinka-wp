// калькулятор в hero
const chips=document.querySelectorAll('.chip');
const d=new Date(),dd=document.getElementById('r-date');if(dd)dd.textContent=d.toLocaleDateString('uk-UA');
document.querySelectorAll('.dd-btn').forEach(b=>b.addEventListener('click',e=>{e.preventDefault();const p=b.parentElement;p.classList.toggle('open');b.setAttribute('aria-expanded',p.classList.contains('open'))}));
chips.forEach(c=>c.addEventListener('click',()=>{
  chips.forEach(x=>x.setAttribute('aria-pressed','false'));
  c.setAttribute('aria-pressed','true');
  document.getElementById('r-price').textContent=c.dataset.price;
  document.getElementById('r-term').textContent=c.dataset.term;
  const ro=document.getElementById('r-obj');if(ro)ro.textContent=c.dataset.name;
  const n=c.dataset.name.toLowerCase(), rb=document.getElementById('r-btn');
  rb.textContent=(rb.dataset.prefix||'Замовити оцінку: ')+n;
}));
// вкладки услуг
document.querySelectorAll('.tab').forEach(t=>t.addEventListener('click',()=>{
  document.querySelectorAll('.tab').forEach(x=>x.setAttribute('aria-selected','false'));
  t.setAttribute('aria-selected','true');
  document.querySelectorAll('.panel').forEach(p=>p.hidden=p.id!==t.dataset.panel);
}));

const burger=document.querySelector('.burger'),menu=document.querySelector('nav.menu');
if(burger&&menu){burger.addEventListener('click',()=>{menu.classList.toggle('open');burger.setAttribute('aria-expanded',menu.classList.contains('open'))});}

// форма заявки → send.php
document.querySelectorAll('form.lead-form').forEach(f=>{
  const pg=f.querySelector('input[name=page]'); if(pg) pg.value=location.pathname+' · '+document.title;
  f.addEventListener('submit',async e=>{
    e.preventDefault();
    const btn=f.querySelector('button[type=submit]'),msg=f.querySelector('.form-msg');
    btn.disabled=true; const t=btn.textContent; btn.textContent='Надсилаємо…'; if(msg){msg.textContent='';msg.className='form-msg';}
    try{
      const r=await fetch(f.getAttribute('action')||'/send.php',{method:'POST',body:new FormData(f),headers:{'Accept':'application/json'}});
      const j=await r.json().catch(()=>({ok:false}));
      if(r.ok&&j.ok){f.reset();btn.textContent='Заявку надіслано ✓';if(msg){msg.textContent='Дякуємо! Передзвонимо протягом 15 хвилин у робочий час.';msg.classList.add('ok');}}
      else throw new Error(j.error||'send');
    }catch(err){
      btn.disabled=false;btn.textContent=t;
      if(msg){msg.textContent='Не вдалося надіслати. Зателефонуйте: +38 (067) 503-46-12';msg.classList.add('err');}
    }
  });
});
