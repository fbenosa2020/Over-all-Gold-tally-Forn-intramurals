<?php
require __DIR__.'/bootstrap.php';
require_login();

$e=event((int)($_GET['event']??0));
if(!$e){
 http_response_code(404);
 exit('Event not found');
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Blank Judge Score Sheet | <?=h($e['name'])?></title>
<link rel="stylesheet" href="assets/style.css">
<style>
@page{size:legal landscape;margin:0.3in 0.35in}
.team-sheet{font-family:Arial,sans-serif;color:#142b48;background:#fff;padding:8px 0 24px}.sheet-brand{display:grid;grid-template-columns:85px minmax(0,1fr) 85px;align-items:center;gap:15px;border-bottom:3px solid #c6ad84;padding:4px 0 12px;margin-bottom:13px;text-align:center}.sheet-brand img{width:74px;height:74px;object-fit:contain}.sheet-brand .brand-left{justify-self:start}.sheet-brand .brand-right{justify-self:end}.sheet-brand strong{font-size:17px}.sheet-brand small{display:block;font-size:11px;color:#51627a}.team-sheet .sheet-signatures{display:flex;justify-content:space-between;gap:24px;margin-top:38px;color:#172b49}.sheet-signatures>div{flex:1;text-align:center;font-size:12px;line-height:1.8}.sheet-notes{margin-top:16px;font-size:12px}.team-score-grid thead th{background:#eaf0f8!important;color:#152f51;font-weight:700!important}.team-score-grid tbody tr:nth-child(even){background:#f7f9fc}.team-score-grid tbody td:first-child{font-weight:700!important}
.team-sheet h1{text-align:center;font-size:27px;font-weight:400;margin:0 0 5px;letter-spacing:.4px}
.team-sheet .judge-field{font-size:15px;margin:5px 0 12px}
.team-score-grid{width:100%;border-collapse:collapse;table-layout:fixed;background:white;color:#111}
.team-score-grid th,.team-score-grid td{border:1px solid #111;padding:5px 4px;font-size:13px;vertical-align:middle}
.team-score-grid thead th{font-weight:400;text-align:left;overflow-wrap:anywhere;line-height:1.3}
.team-score-grid th:first-child{width:15%}
.team-score-grid th:last-child{width:12%;font-weight:600}
.pageant-score-grid th:first-child{width:48%}.pageant-score-grid th:nth-child(2){width:22%}.pageant-score-grid th:last-child{width:30%}.team-score-grid.pageant-score-grid tbody td:first-child{white-space:normal;overflow-wrap:anywhere}.pageant-final-grid th:first-child{width:55%;white-space:normal}.pageant-final-grid th:last-child{width:45%}
.team-score-grid tbody tr{height:60px}
.team-score-grid tbody td:first-child{font-weight:500;font-size:15px;white-space:nowrap}
.team-score-grid tbody td:not(:first-child){text-align:center}
@media print{body.print-only{background:#fff!important}.blank-score-sheet:has(.team-sheet){margin:0!important;padding:0!important;border:0!important;box-shadow:none!important;max-width:none!important}.team-sheet{padding:0;max-width:none;width:100%}.team-score-grid tbody tr{height:40px}.sheet-brand img{width:58px;height:58px}.sheet-brand{grid-template-columns:65px minmax(0,1fr) 65px}.sheet-brand{margin-bottom:10px}.team-sheet .sheet-signatures{margin-top:30px}.blank-score-sheet:has(.pageant-sheet){display:block!important;width:100%!important;max-width:none!important;min-width:0!important;margin:0!important;padding:0!important;overflow:visible!important;box-sizing:border-box!important}.pageant-sheet{display:block!important;width:100%!important;max-width:none!important;min-width:0!important;box-sizing:border-box!important;margin:0!important;padding:0!important;break-inside:avoid;page-break-inside:avoid;overflow:visible!important}.pageant-sheet h1{font-size:15px;margin:1px 0 3px}.pageant-sheet .sheet-brand{grid-template-columns:38px minmax(0,1fr) 38px;gap:5px;padding:0 0 3px;margin:0 0 3px;border-bottom-width:1px;min-width:0}.pageant-sheet .sheet-brand img{width:30px;height:30px}.pageant-sheet .sheet-brand strong{font-size:10px}.pageant-sheet .sheet-brand small{font-size:7px;line-height:1.1}.pageant-sheet .judge-field{font-size:9px;margin:1px 0 4px}.pageant-sheet p{font-size:8px;line-height:1.1;margin:2px 0}.pageant-sheet .team-score-grid{display:table!important;width:100%!important;max-width:100%!important;min-width:0!important;table-layout:fixed!important;margin-left:0!important;margin-right:0!important}.pageant-sheet .team-score-grid th,.pageant-sheet .team-score-grid td{padding:2px 4px;font-size:9px;line-height:1.1;overflow-wrap:anywhere;box-sizing:border-box}.pageant-sheet .team-score-grid tbody tr{height:22px}.pageant-sheet .sheet-notes{font-size:8px;margin:4px 0}.pageant-sheet .sheet-signatures{gap:12px;margin-top:5px;font-size:8px;line-height:1.2}.pageant-final-sheet{margin-top:8px!important}tant} }
</style>
</head>
<body class="print-only">
<main class="panel blank-score-sheet">
 <?php if(score_type($e)==='judged'): ?>
 <?php if($e['id']===33): ?>
 <section class="team-sheet pageant-sheet">
 <div class="sheet-brand"><img class="brand-left" src="assets/hccci-shield.jpg" alt="Holy Child Central Colleges logo"><div><strong>HOLY CHILD CENTRAL COLLEGES, INC.</strong><small>HOLINIAN CARDINALS’ FESTIA 2026 · OFFICIAL JUDGING SHEET</small><small>September 28 – October 3, 2026</small></div><img class="brand-right" src="assets/dsa-logo.jpg" alt="DSA logo"></div>
 <h1><?=h(strtoupper($e['name']))?> · PRELIMINARY EXPOSURES</h1>
 <div class="judge-field">Candidate: __________________________ &nbsp; Gender: __________ &nbsp; Nation: ____________________</div>
 <table class="team-score-grid pageant-score-grid"><thead><tr><th>IRR criterion</th><th>Maximum (%)</th><th>Percentage awarded</th></tr></thead><tbody><?php foreach($e['criteria'] as $c):?><tr><td><?=h($c['label'])?></td><td><?=number_format((float)$c['max'],2)?>%</td><td></td></tr><?php endforeach;?></tbody><tfoot><tr><th>Preliminary overall score</th><th>100%</th><th></th></tr></tfoot></table>
 <p class="sheet-notes"><b>Remarks / authorized deductions:</b> ____________________________________________________________</p>
 <div class="sheet-signatures"><div>________________________________<br>JUDGE’S SIGNATURE<br>Printed name: __________________________</div><div>________________________________<br>RECEIVED / VERIFIED BY TABULATOR<br>Printed name: __________________________</div></div>
 </section>
 <section class="team-sheet pageant-sheet pageant-final-sheet">
 <div class="sheet-brand"><img class="brand-left" src="assets/hccci-shield.jpg" alt="Holy Child Central Colleges logo"><div><strong>HOLY CHILD CENTRAL COLLEGES, INC.</strong><small>HOLINIAN CARDINALS’ FESTIA 2026 · OFFICIAL JUDGING SHEET</small><small>September 28 – October 3, 2026</small></div><img class="brand-right" src="assets/dsa-logo.jpg" alt="DSA logo"></div>
 <h1><?=h(strtoupper($e['name']))?> · FINAL INTERVIEW</h1>
 <div class="judge-field">Candidate: __________________________ &nbsp; Gender: __________ &nbsp; Nation: ____________________</div>
 <p>Final Interview scoring starts at zero. Preliminary exposure scores do not carry into this stage.</p>
 <table class="team-score-grid pageant-final-grid"><thead><tr><th>Final Interview score (%)</th><th>Score out of 100%</th></tr></thead><tbody><tr><td>Final Interview</td><td></td></tr></tbody></table>
 <p class="sheet-notes"><b>Remarks:</b> ___________________________________________________________________________________</p>
 <div class="sheet-signatures"><div>________________________________<br>JUDGE’S SIGNATURE<br>Printed name: __________________________</div><div>________________________________<br>RECEIVED / VERIFIED BY TABULATOR<br>Printed name: __________________________</div></div>
 </section>
 <div class="no-print"><button type="button" onclick="window.print()">Export PDF</button></div>
 <?php else: ?>
 <section class="team-sheet">
 <div class="sheet-brand"><img class="brand-left" src="assets/hccci-shield.jpg" alt="Holy Child Central Colleges logo"><div><strong>HOLY CHILD CENTRAL COLLEGES, INC.</strong><small>HOLINIAN CARDINALS’ FESTIA 2026 · OFFICIAL JUDGING SHEET</small><small>September 28 – October 3, 2026</small></div><img class="brand-right" src="assets/dsa-logo.jpg" alt="DSA logo"></div>
  <h1><?=h(strtoupper($e['name']))?></h1>
  <div class="judge-field">Judge: __________________________________________ &nbsp; Date: __________________</div>
  <table class="team-score-grid">
   <thead><tr><th>TEAMS</th>
    <?php foreach($e['criteria'] as $c): ?><th><?=h($c['label'])?> (<?=number_format((float)$c['max']/array_sum(array_column($e['criteria'],'max'))*100,2)?>%)</th><?php endforeach; ?>
    <th>Overall score (out of 100%)</th>
   </tr></thead>
   <tbody>
    <?php foreach(['Water Tribe','Air Nomads','Fire Nation','Earth Kingdom'] as $nation): ?>
     <tr><td><?=h($nation)?></td><?php foreach($e['criteria'] as $_): ?><td></td><?php endforeach; ?><td></td></tr>
    <?php endforeach; ?>
   </tbody>
  </table>
 <p class="sheet-notes"><b>Remarks / authorized deductions:</b> ____________________________________________________________</p>
 <div class="sheet-signatures"><div>________________________________<br>JUDGE’S SIGNATURE<br>Printed name: __________________________</div><div>________________________________<br>RECEIVED / VERIFIED BY TABULATOR<br>Printed name: __________________________</div></div>
 </section>
 <div class="no-print"><button type="button" onclick="window.print()">Export PDF</button></div>
 <?php endif; ?>
 <?php else: ?>
 <section class="team-sheet">
 <div class="sheet-brand"><img class="brand-left" src="assets/hccci-shield.jpg" alt="Holy Child Central Colleges logo"><div><strong>HOLY CHILD CENTRAL COLLEGES, INC.</strong><small>HOLINIAN CARDINALS’ FESTIA 2026 · OFFICIAL JUDGING SHEET</small><small>September 28 – October 3, 2026</small></div><img class="brand-right" src="assets/dsa-logo.jpg" alt="DSA logo"></div>
  <h1><?=h(strtoupper($e['name']))?></h1>
  <div class="judge-field">Official / tabulator: __________________________________ &nbsp; Date: __________________</div>
  <p><?=score_type($e)==='quiz'?'Record each round as a percentage contribution to the overall score.':'Record official results or placements; the IRR does not specify a complete numerical judging rubric for this event.'?></p>
  <table class="team-score-grid">
   <thead><tr><th>TEAMS</th>
    <?php if(score_type($e)==='quiz'):foreach($e['criteria'] as $c):?><th><?=h($c['label'])?> (<?=number_format((float)$c['max']/array_sum(array_column($e['criteria'],'max'))*100,2)?>%)</th><?php endforeach;else:?>
    <th>Division / Category</th><th>Official result / Placement</th><th>Remarks</th>
    <?php endif;?><th><?=score_type($e)==='quiz'?'Total (%)':'Gold awarded (if applicable)'?></th></tr></thead>
   <tbody><?php foreach(['Water Tribe','Air Nomads','Fire Nation','Earth Kingdom'] as $nation):?>
    <tr><td><?=h($nation)?></td><?php for($i=0;$i<(score_type($e)==='quiz'?count($e['criteria']):3);$i++):?><td></td><?php endfor;?><td></td></tr>
   <?php endforeach;?></tbody>
  </table>
  <p class="sheet-notes"><b>Notes / authorized deductions:</b> ____________________________________________________________</p>
  <div class="sheet-signatures"><div>________________________________<br>JUDGE / EVENT OFFICIAL’S SIGNATURE<br>Printed name: __________________________</div><div>________________________________<br>RECEIVED / VERIFIED BY TABULATOR<br>Printed name: __________________________</div></div>
 </section>
 <div class="no-print"><button type="button" onclick="window.print()">Export PDF</button></div>
 <?php /* Original individual score form is retained below for non-judged events but hidden from printing. */ ?>
 <div class="no-print">
 <div class="score-layout">
  <aside class="guidelines-panel">
    <h3>EVENT GUIDELINES</h3>
   <p class="guidelines-note"><?=nl2br(h($e['irr']))?></p>
  </aside>
  <section class="score-panel">
   <div class="judge-line"><b>Judge / official:</b> __________________________________________</div>
   <?php if(score_type($e)!=='result'): ?>
   <table class="data">
    <thead><tr><th>Criterion / round</th><th>Maximum (%)</th><th>Percentage awarded</th></tr></thead>
    <tbody><?php foreach($e['criteria'] as $c): ?><tr><td><?=h($c['label'])?></td><td><?=number_format((float)$c['max']/array_sum(array_column($e['criteria'],'max'))*100,2)?>%</td><td class="write-cell"></td></tr><?php endforeach; ?></tbody>
    <tfoot><tr><th>Overall score (out of 100%)</th><th></th><th class="write-cell"></th></tr></tfoot>
   </table>
   <?php else: ?>
   <table class="data"><tr><th>Authorized numeric result</th><th class="write-cell"></th></tr></table>
   <?php endif; ?>
   <table class="data score-summary">
    <tr><th>Authorized deduction (optional)</th><td class="write-cell"></td><th>Final score</th><td class="write-cell"></td></tr>
    <tr><th>Match result / winner (optional)</th><td colspan="3" class="write-cell"></td></tr>
   </table>
   <p><b>Remarks / deduction basis:</b></p>
   <div class="writing-area"></div>
  </section>
 </div>
 <div class="signatures judge-signatures"><div>____________________<br>JUDGE 1<br>Name: ____________________</div><div>____________________<br>JUDGE 2<br>Name: ____________________</div><div>____________________<br>HEAD JUDGE / TABULATOR<br>Name: ____________________</div></div>
 </div>
 <?php endif; ?>
</main>
</body>
</html>
