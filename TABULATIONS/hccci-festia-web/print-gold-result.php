<?php
require __DIR__.'/bootstrap.php';
require_login();
ensure_gold_schema();
$eventId=(int)($_GET['event']??0);
$e=event($eventId);
if(!$e){http_response_code(404);exit('Event not found');}
$divisionOptions=event_division_options($e);
$divisionFilter=trim((string)($_GET['division']??''));
if($divisionFilter!==''&&!in_array($divisionFilter,$divisionOptions,true)){http_response_code(400);exit('Invalid division');}
if($divisionFilter!==''){$divisionValues=division_values($e,$divisionFilter);$placeholders=implode(',',array_fill(0,count($divisionValues),'?'));$query=db()->prepare('SELECT * FROM gold_awards WHERE event_id=? AND division IN ('.$placeholders.') ORDER BY (placement IS NULL),placement ASC,id ASC');$query->execute([$eventId,...$divisionValues]);}
else{$query=db()->prepare('SELECT * FROM gold_awards WHERE event_id=? ORDER BY (placement IS NULL),placement ASC,id ASC');$query->execute([$eventId]);}
$awards=$query->fetchAll();
$total=array_sum(array_column($awards,'gold_points'));
$divisionGroups=[];
foreach($awards as $award){$division=trim(division_label($e,(string)$award['division']));$divisionGroups[$division][]=$award;}
if($divisionGroups===[])$divisionGroups['']=[];
if($divisionOptions!==[]){$orderedGroups=[];foreach($divisionOptions as $divisionOption)if(isset($divisionGroups[$divisionOption])){$orderedGroups[$divisionOption]=$divisionGroups[$divisionOption];unset($divisionGroups[$divisionOption]);}foreach($divisionGroups as $division=>$group)$orderedGroups[$division]=$group;$divisionGroups=$orderedGroups;}
$multipleAwardGroups=count($divisionGroups)>1;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($e['name'])?> | Gold Award Result</title>
<link rel="stylesheet" href="assets/style.css">
<style>
@page{size:legal landscape;margin:12mm}
.event-result{max-width:1100px;margin:24px auto}
.event-result .print-head{display:flex;align-items:center;justify-content:center;gap:18px;text-align:center;border-bottom:2px solid #c9a76b;padding-bottom:16px;margin-bottom:20px}
.event-result .print-head img{width:72px;height:72px;object-fit:contain}
.event-result h1{margin:4px 0}
.event-result h3{margin:18px 0 8px}
.event-result .data{width:100%}
.event-result .total{text-align:right;font-weight:700}
.event-result .event-total{text-align:right;font-size:1.1rem;font-weight:700;margin-top:12px;padding:10px;background:#f3e3bd}
@media print{.event-result{max-width:none;margin:0;padding:0;box-shadow:none;border:0}.event-result .print-head img{width:58px;height:58px}}
</style>
</head>
<body class="print-only">
<main class="panel event-result">
<div class="print-head"><img src="assets/logo.jpg" alt="HCCCI logo"><div><strong>HOLY CHILD CENTRAL COLLEGES, INC.</strong><h1>FesTia 2026 · Gold Award Result</h1><p>September 28 – October 3, 2026</p></div></div>
<h2><?=h($e['name'])?></h2>
<?php foreach($divisionGroups as $division=>$group):?>
<?php if($division!==''):?><h3><?=h($division)?></h3><?php endif;?>
<table class="data">
<thead><tr><th>Rank</th><th>Event</th><th>Division</th><th>Award</th><th>Nation</th><th>Winner / recipient</th><th>Gold</th><th>Notes</th></tr></thead>
<tbody><?php foreach($group as $award):?><tr><td><?=$award['placement']!==null?(int)$award['placement'].ordinal_suffix((int)$award['placement']):'—'?></td><td><?=h($e['name'])?></td><td><?=h(division_label($e,(string)$award['division']))?></td><td><?=h($award['award_name'])?></td><td><?=h($award['team'])?></td><td><?=h($award['winner_name'])?></td><td><?=number_format((float)$award['gold_points'],2)?></td><td><?=h($award['notes'])?></td></tr><?php endforeach;?><?php if(!$group):?><tr><td colspan="8" class="empty">No gold awards recorded for this event.</td></tr><?php endif;?></tbody>
<tfoot><tr><th colspan="6" class="total"><?=$multipleAwardGroups?h($division!==''?$division.' total':'Unassigned division total'):'Selected event total'?></th><th><?=number_format(array_sum(array_column($group,'gold_points')),2)?></th><th></th></tr></tfoot>
</table>
<?php endforeach;?>
<?php if($multipleAwardGroups):?><p class="event-total">Selected event total: <?=number_format((float)$total,2)?></p><?php endif;?>
<div class="signatures"><div>_________________________<br>HEAD TABULATOR</div><div>_________________________<br>VERIFIED / APPROVED BY</div></div>
<div class="no-print"><button type="button" onclick="window.print()">Print event result</button></div>
</main>
</body>
</html>
