<?php
require __DIR__.'/bootstrap.php';
require_login();
$s=db()->prepare('SELECT * FROM scores WHERE id=?');
$s->execute([(int)($_GET['id']??0)]);
$r=$s->fetch();
if(!$r){http_response_code(404);exit('Score sheet not found');}
$e=event((int)$r['event_id']);
$details=json_decode($r['details'],true)?:[];
$allRows=rows((int)$r['event_id']);
$provisionalRank=provisional_rank_for_score($r,$allRows);
$rankText=$provisionalRank===null?'—':$provisionalRank . ordinal_suffix($provisionalRank);
$scoreStage=(string)($details['_stage']??'preliminary');
$isFinalInterview=(int)$e['id']===33&&$scoreStage==='final';
$hasRubric=score_type($e)!=='result'&&!$isFinalInterview;
$maxTotal=$hasRubric?array_sum(array_column($e['criteria'],'max')):($isFinalInterview?100:0);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Score Sheet | <?=h($e['name'])?></title>
<link rel="stylesheet" href="assets/style.css">
<style>
@page{size:legal landscape;margin:0.32in 0.35in}
body{margin:0}
.two-school-logos{display:grid;grid-template-columns:90px minmax(0,1fr) 90px;align-items:center;gap:15px}
.two-school-logos img{width:85px;height:85px;object-fit:contain}
main.panel{max-width:none;width:100%;margin:0;padding:0;border:0;box-shadow:none}
main table.data{width:100%;table-layout:fixed;border-collapse:collapse}
main table.data th,main table.data td{padding:8px 10px;word-break:break-word;overflow-wrap:anywhere}
@media print{.two-school-logos{grid-template-columns:70px minmax(0,1fr) 70px}.two-school-logos img{width:65px;height:65px}main.panel{padding:0}.print-head{margin-bottom:8px}.signatures{gap:10px}.signatures>div{font-size:11px;line-height:1.5}}
</style>
</head>
<body class="print-only">
<main class="panel">
<div class="print-head two-school-logos">
<img src="assets/hccci-shield.jpg" alt="Holy Child Central Colleges logo">
<div><strong>HOLY CHILD CENTRAL COLLEGES, INC.</strong><h1>FesTia 2026 · Judge Score Sheet</h1></div>
<img src="assets/logo.jpg" alt="Previously provided HCCCI logo">
</div>
<h2><?=h($e['name'])?></h2>
<p><b>Nation:</b> <?=h($r['team'])?> &nbsp; <b>Contestant:</b> <?=h($r['contestant'])?> &nbsp; <b>Division:</b> <?=h($r['division'])?><?php if((int)$e['id']===33):?> &nbsp; <b>Stage:</b> <?=h($isFinalInterview?'Final Interview':'Preliminary Exposures')?><?php endif;?><?php if($provisionalRank!==null):?> &nbsp; <b>Provisional Rank:</b> <?=h($rankText)?><?php endif;?></p>
<p><b>Judge / official:</b> <?=h($r['judge'])?></p>
<?php if($hasRubric):?>
<table class="data">
<thead><tr><th>Criterion</th><th>Maximum (%)</th><th>Awarded (%)</th></tr></thead>
<tbody><?php foreach($e['criteria'] as $i=>$c):$awarded=$details[(string)$i]??null;?><tr><td><?=h($c['label'])?></td><td><?=number_format((float)$c['max']/$maxTotal*100,2)?>%</td><td><?=$awarded===null?'—':number_format((float)$awarded/$maxTotal*100,2).'%'?></td></tr><?php endforeach;?></tbody>
</table>
<?php endif;?>
<table class="data">
<tr><th><?=($hasRubric||$isFinalInterview)?'Raw (out of 100%)':'Raw'?></th><th>Deduction (IRR points)</th><th><?=($hasRubric||$isFinalInterview)?'Final (out of 100%)':'Final'?></th><th>Match result</th></tr>
<tr><td><?=$r['raw']===null?'—':(($hasRubric||$isFinalInterview)?number_format((float)$r['raw']/$maxTotal*100,2).'% / 100%':h($r['raw']))?></td><td><?=h($r['deduction'])?></td><td><?=$r['final']===null?'—':(($hasRubric||$isFinalInterview)?number_format((float)$r['final']/$maxTotal*100,2).'% / 100%':h($r['final']))?></td><td><?=h($r['match_result'])?></td></tr>
</table>
<p><b>Remarks:</b> <?=nl2br(h($r['remarks']))?></p>
<div class="signatures"><div>_________________________<br><?=h($r['judge'])?><br>JUDGE / OFFICIAL SIGNATURE</div><div>_________________________<br>TABULATOR SIGNATURE</div></div>
<div class="no-print"><button type="button" onclick="window.print()">Export PDF</button></div>
</main>
</body>
</html>