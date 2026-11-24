/* Калькулятор податків при угоді з нерухомістю — Україна, ставки станом на 2026 рік.
   Усі ставки — в одному місці, щоб оновлювати при зміні законодавства. */
const RATES = {
  updated: "жовтень 2026",
  pdfo_low: 0.05,      // ПДФО 5 %
  pdfo_high: 0.18,     // ПДФО 18 %
  vz: 0.05,            // військовий збір 5 % (нараховується лише коли ПДФО > 0)
  duty: 0.01,          // держмито / плата нотаріусу за посвідчення договору — 1 %
  pension: 0.01,       // збір до ПФУ при купівлі-продажу — 1 %, платить покупець
};
const fmt = n => Math.round(n).toLocaleString("uk-UA") + " грн";
const pct = r => (r*100).toLocaleString("uk-UA") + " %";

function calc(d){
  const V = d.value, rows = [], notes = [];
  let pdfo = 0, base = V, why = "";
  if (d.deal === "sale") {
    if (d.nonres) { pdfo = RATES.pdfo_high; why = "продавець — нерезидент"; }
    else if (d.saleNo === "1" && (d.owned3 || d.inherited)) { pdfo = 0; why = d.inherited ? "перший продаж за рік, майно отримане у спадщину — строк володіння не має значення" : "перший продаж за рік і у власності понад 3 роки"; }
    else if (d.saleNo === "1") { pdfo = RATES.pdfo_low; why = "перший продаж за рік, але у власності менше 3 років"; }
    else if (d.saleNo === "2") { pdfo = RATES.pdfo_low; why = "другий продаж житла за рік"; }
    else { pdfo = RATES.pdfo_high; why = "третій і наступний продаж за рік"; if (d.cost>0 && d.cost<V) { base = V - d.cost; notes.push("Для 18 % базу зменшено на документально підтверджені витрати на придбання: "+fmt(V)+" − "+fmt(d.cost)+" = "+fmt(base)+"."); } }
    rows.push(["ПДФО продавця", pdfo*base, pct(pdfo)+" · "+why]);
    rows.push(["Військовий збір продавця", pdfo>0 ? RATES.vz*base : 0, pdfo>0 ? pct(RATES.vz) : "не нараховується, бо ПДФО 0 %"]);
    rows.push(["Держмито / плата за посвідчення договору", RATES.duty*V, pct(RATES.duty)+" · за домовленістю сторін, часто навпіл"]);
    rows.push(["Збір до Пенсійного фонду (покупець)", d.firstHome ? 0 : RATES.pension*V, d.firstHome ? "звільнення: покупець купує житло вперше" : pct(RATES.pension)]);
    notes.push("Податки рахують від більшої з двох сум — ціни в договорі або оціночної вартості. Тому заниження ціни в договорі не зменшує податок.");
    if (pdfo>0) notes.push("Продавець подає декларацію до 1 травня наступного року; нотаріус утримує й перераховує податок при посвідченні угоди.");
  } else {
    const gift = d.deal === "gift";
    if (d.nonres) { pdfo = RATES.pdfo_high; why = (gift?"дарувальник":"спадкодавець")+" або отримувач — нерезидент"; }
    else if (d.degree === "12") { pdfo = 0; why = "родичі першого чи другого ступеня споріднення"; }
    else { pdfo = RATES.pdfo_low; why = "отримувач не є родичем першого/другого ступеня"; }
    rows.push(["ПДФО отримувача", pdfo*V, pct(pdfo)+" · "+why]);
    rows.push(["Військовий збір отримувача", pdfo>0 ? RATES.vz*V : 0, pdfo>0 ? pct(RATES.vz) : "не нараховується, бо ПДФО 0 %"]);
    if (gift) rows.push(["Держмито / плата за посвідчення договору дарування", RATES.duty*V, pct(RATES.duty)]);
    else rows.push(["Плата нотаріусу за свідоцтво про спадщину", null, "у приватного нотаріуса — за домовленістю (орієнтовно від кількох тисяч грн); держмито у державного — символічне"]);
    rows.push(["Збір до Пенсійного фонду", 0, "не сплачується — тільки при купівлі-продажу"]);
    if (pdfo===0) notes.push("Перший ступінь: батьки, діти (зокрема усиновлені), подружжя. Другий: рідні брати й сестри, баба, дід, онуки.");
    if (pdfo>0) notes.push("Отримувач подає річну декларацію до 1 травня і сплачує податок до 1 серпня наступного року.");
  }
  rows.push(["Звіт про оцінку (потрібен нотаріусу)", d.object==="car" ? 800 : 900, "наша ціна, реєстрація у ФДМУ включена"]);
  const total = rows.reduce((s,r)=>s+(r[1]||0),0);
  return {rows, total, notes};
}

function render(){
  const f = document.getElementById("calc-form"); if(!f) return;
  const d = {
    deal: f.deal.value, object: f.object.value,
    value: Math.max(0, parseFloat(String(f.value.value).replace(/[^\d.]/g,""))||0),
    saleNo: f.saleNo.value, owned3: f.owned3.checked, inherited: f.inherited.checked,
    cost: parseFloat(String(f.cost.value).replace(/[^\d.]/g,""))||0,
    firstHome: f.firstHome.checked, degree: f.degree.value, nonres: f.nonres.checked,
  };
  f.querySelectorAll("[data-for]").forEach(el=>{ el.hidden = !el.dataset.for.split(" ").includes(d.deal); });
  f.querySelector("[data-cost]").hidden = !(d.deal==="sale" && d.saleNo==="3");
  const out = document.getElementById("calc-out");
  const r = calc(d);
  out.innerHTML = `<table class="calc-table"><tbody>${r.rows.map(([n,v,c])=>`<tr><td><b>${n}</b><br><small>${c}</small></td><td>${v===null?"—":fmt(v)}</td></tr>`).join("")}</tbody>
  <tfoot><tr><td><b>Разом обов'язкових платежів</b><br><small>без послуг нотаріуса, які встановлюються індивідуально</small></td><td><b>${fmt(r.total)}</b></td></tr></tfoot></table>
  ${r.notes.map(n=>`<p class="calc-note">${n}</p>`).join("")}
  <p class="calc-cta"><a class="btn btn-primary" href="#order">Замовити оцінку для цієї угоди</a></p>`;
}
document.addEventListener("input", e=>{ if(e.target.closest("#calc-form")) render(); });
document.addEventListener("change", e=>{ if(e.target.closest("#calc-form")) render(); });
document.addEventListener("DOMContentLoaded", ()=>{ const u=document.getElementById("calc-updated"); if(u) u.textContent=RATES.updated; render(); });
