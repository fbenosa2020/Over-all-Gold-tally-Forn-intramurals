<?php
declare(strict_types=1);
session_start(['cookie_httponly'=>true,'cookie_samesite'=>'Lax','use_strict_mode'=>true]);
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
const ROOT=__DIR__;
function db(): PDO {
 static $db=null; if($db)return $db;
 if (!is_file(ROOT.'/config.php')) { throw new RuntimeException('Missing config.php: copy config.example.php to config.php in '.ROOT); }
 $config=require ROOT.'/config.php';
 $dsn=sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',$config['host'],$config['port'],$config['database']);
 $db=new PDO($dsn,$config['username'],$config['password'],[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES=>false
 ]);
 return $db;
}
/* Schema is installed with database/schema.sql, not created during requests. */
function ensure_gold_schema():void{
 static $ready=false;if($ready)return;
 $db=db();
 if(!$db->query("SHOW COLUMNS FROM gold_awards LIKE 'winner_name'")->fetch())$db->exec("ALTER TABLE gold_awards ADD COLUMN winner_name VARCHAR(255) NOT NULL DEFAULT '' AFTER team");
 $ready=true;
}
function ensure_audit_schema():void{}

function actor():array{return [(int)($_SESSION['uid']??0),(string)($_SESSION['username']??'anonymous')];}
function admin():bool{return logged() && ($_SESSION['role']??'')==='admin';}
function ensure_default_admin():void{
    $db=db();
    $username='admin';
    $password='Admin@123456';
    $hash=password_hash($password,PASSWORD_DEFAULT);

    $stmt=$db->prepare('SELECT id FROM users WHERE username=? LIMIT 1');
    $stmt->execute([$username]);
    $existing=$stmt->fetchColumn();

    if($existing!==false){
        $db->prepare('UPDATE users SET password_hash=?, role=? WHERE username=?')->execute([$hash,'admin',$username]);
        return;
    }

    $db->prepare('INSERT INTO users(username,password_hash,role) VALUES(?,?,?)')->execute([
        $username,
        $hash,
        'admin'
    ]);
    $id=(int)$db->lastInsertId();
    audit('CREATE','user',$id,null,['username'=>$username,'role'=>'admin'],'system',null);
}
function require_admin():void{require_login();if(!admin())throw new RuntimeException('Administrator permission required.');}
function audit(string $action,string $entity,int $id=0,?array $before=null,?array $after=null,?string $username=null,?int $actorId=null):void{
 $db=db();[$uid,$name]=actor();$name=$username??$name;$uid=$actorId??$uid;
 $prev=(string)($db->query('SELECT entry_hash FROM audit_logs ORDER BY id DESC LIMIT 1')->fetchColumn()?:str_repeat('0',64));
 $time=gmdate('Y-m-d\TH:i:s\Z');$beforeJ=$before===null?null:json_encode($before,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);$afterJ=$after===null?null:json_encode($after,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
 $ip=substr((string)($_SERVER['REMOTE_ADDR']??''),0,64);
 $payload=json_encode([$uid,$name,$action,$entity,$id,$beforeJ,$afterJ,$ip,$time,$prev],JSON_THROW_ON_ERROR);
 $hash=hash('sha256',$payload);
 $stmt=$db->prepare('INSERT INTO audit_logs(actor_id,actor_username,action,entity_type,entity_id,before_json,after_json,ip_address,occurred_at,previous_hash,entry_hash) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
 $stmt->execute([$uid,$name,$action,$entity,$id?:null,$beforeJ,$afterJ,$ip,$time,$prev,$hash]);
}
function row_by_id(string $table,int $id):?array{if(!in_array($table,['scores','gold_awards','users'],true))throw new RuntimeException('Invalid audit table');$s=db()->prepare('SELECT * FROM '.$table.' WHERE id=?');$s->execute([$id]);return $s->fetch()?:null;}
function audit_time(string $utc):string{try{return (new DateTimeImmutable($utc,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Manila'))->format('M d, Y h:i:s A').' PHT';}catch(Throwable $e){return $utc;}}
function events():array{static $e=null;return $e??=json_decode(file_get_contents(ROOT.'/data/events.json'),true,512,JSON_THROW_ON_ERROR);}
function event(int $id):?array{foreach(events() as $e)if($e['id']===$id)return $e;return null;}
function h($s):string{return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf():string{return $_SESSION['csrf']??=bin2hex(random_bytes(32));}
function verify():void{if(!hash_equals(csrf(),(string)($_POST['csrf']??'')))throw new RuntimeException('Invalid security token. Refresh and retry.');}
function logged():bool{return isset($_SESSION['uid']);}
function require_login():void{if(!logged()){header('Location: ?page=login');exit;}}
function redirect(string $url):never{header('Location: '.$url);exit;}
function flash(string $s):void{$_SESSION['flash']=$s;}
function get_flash():string{$s=$_SESSION['flash']??'';unset($_SESSION['flash']);return $s;}
function score_type(array $e):string{if($e['type']==='judged' && abs(array_sum(array_column($e['criteria'],'max'))-100)<0.01)return 'judged';return $e['type']==='quiz'?'quiz':'result';}
function total(array $e,array $d):?float{if(score_type($e)==='result')return isset($d['manual'])&&is_numeric($d['manual'])?(float)$d['manual']:null;$sum=0;$any=false;foreach($e['criteria'] as $i=>$c){$v=$d[(string)$i]??null;if($v===null||$v==='')continue;if(!is_numeric($v)||$v<0||$v>$c['max'])throw new RuntimeException('Score exceeds permitted points for '.$c['label']);$sum+=(float)$v;$any=true;}return $any?$sum:null;}
function event_division_options(array $event):array{$text=strtolower((string)($event['irr']??'').' '.($event['name']??''));if(($event['id']??null)===33)return ['Male','Female'];if(($event['id']??null)===24)return ['Boys Singles A','Boys Singles B','Women Singles A','Women Singles B',"Men's Doubles","Women's Doubles"];if(($event['id']??null)===19)return ['Men','Women',"Men's Doubles","Women's Doubles"];if(($event['id']??null)===25)return ['Men','Women','Doubles'];if(in_array(($event['id']??null),[41,43],true))return ['Men','Women'];if(in_array($event['name']??'', ['Volleyball','Basketball','Table Tennis','Chess','Scrabble','Boggle','Games of Generals','Badminton','Dart'],true))return ['Men','Women'];if(preg_match('/\b(boys|girls)\b/', $text)===1)return ['Boys','Girls'];return [];}
function division_values(array $event,string $division):array{if(($event['id']??null)===19&&$division==="Men's Doubles")return ["Men's Doubles",'Doubles'];if(in_array($divisionOptions=event_division_options($event),[['Men','Women'],['Men','Women',"Men's Doubles","Women's Doubles"]],true)){if($division==='Men')return ['Men','Boys'];if($division==='Women')return ['Women','Girls'];}return [$division];}
function division_label(array $event,string $division):string{if(($event['id']??null)===19&&$division==='Doubles')return "Men's Doubles";if(in_array(event_division_options($event),[['Men','Women'],['Men','Women',"Men's Doubles","Women's Doubles"]],true)){if($division==='Boys')return 'Men';if($division==='Girls')return 'Women';}return $division;}
function rows(int $event,string $division=''):array{$q='SELECT * FROM scores WHERE event_id=?';$args=[$event];if($division!==''){$values=division_values(event($event)??[],$division);$q.=' AND division IN ('.implode(',',array_fill(0,count($values),'?')).')';$args=[...$args,...$values];}$q.=' ORDER BY updated_at DESC';$s=db()->prepare($q);$s->execute($args);$rows=$s->fetchAll();$eventData=event($event);if($eventData)foreach($rows as &$row)$row['division']=division_label($eventData,(string)$row['division']);unset($row);return $rows;}
function ordinal_suffix(int $n):string{$m=$n%100;if(in_array($m,[11,12,13],true))return 'th';$r=$n%10;if($r===1)return 'st';if($r===2)return 'nd';if($r===3)return 'rd';return 'th';}
function standings(array $scores,bool $byCandidate=false):array{$teams=[];foreach($scores as $s){$details=json_decode((string)($s['details']??''),true)?:[];$stage=$byCandidate?(string)($details['_stage']??'preliminary'):'';$contestant=$byCandidate?(string)($s['contestant']??''):'';$team=(string)($s['team']??'');$division=(string)($s['division']??'');$key=$team.'|'.$division.'|'.$contestant.'|'.$stage;if(!isset($teams[$key]))$teams[$key]=['team'=>$team,'division'=>$division,'contestant'=>$contestant,'stage'=>$stage,'total'=>0,'count'=>0,'raw_total'=>0,'raw_count'=>0,'entries'=>0,'match'=>[]];$teams[$key]['entries']++;if($s['final']!==null){$teams[$key]['total']+=(float)$s['final'];$teams[$key]['count']++;}if($s['raw']!==null){$teams[$key]['raw_total']+=(float)$s['raw'];$teams[$key]['raw_count']++;}if($s['match_result'])$teams[$key]['match'][]=$s['match_result'];}$teams=array_values($teams);foreach($teams as &$t){$t['average']=$t['count']?$t['total']/$t['count']:null;$t['raw_average']=$t['raw_count']?$t['raw_total']/$t['raw_count']:null;$t['match']=implode('; ',array_unique($t['match']));}unset($t);usort($teams,fn($a,$b)=>($b['average']??-INF)<=>($a['average']??-INF));return $teams;}
function provisional_rank_for_score(array $score,array $scores,bool $byCandidate=false):?int{$team=(string)($score['team']??'');$division=(string)($score['division']??'');$contestant=(string)($score['contestant']??'');$details=json_decode((string)($score['details']??''),true)?:[];$stage=(string)($details['_stage']??'preliminary');$standing=standings($scores,$byCandidate);foreach($standing as $i=>$entry){if($entry['team']===$team&&($entry['division']===$division||$division==='')&&(!$byCandidate||($entry['contestant']===$contestant&&$entry['stage']===$stage))&&$entry['average']!==null)return $i+1;}return null;}

/* Gold tally module ------------------------------------------------------- */
function gold_scales(int $eventId):array{
    $common = [5, 3, 2, 1];
    $danceSport = [15, 10, 8, 6];

    if ($eventId === 19) {
        return ['Men' => [5, 4, 3, 2], 'Women' => [5, 4, 3, 2], "Men's Doubles" => [8, 6, 4, 2], "Women's Doubles" => [8, 6, 4, 2]];
    }
    if (in_array($eventId, [20, 21, 22, 23], true)) {
        return ['Men' => $common, 'Women' => $common];
    }
    if ($eventId === 24) {
        return ['Boys Singles A' => $common, 'Boys Singles B' => $common, 'Women Singles A' => $common, 'Women Singles B' => $common, "Men's Doubles" => [8, 6, 4, 2], "Women's Doubles" => [8, 6, 4, 2]];
    }
    if ($eventId === 25) {
        return ['Men' => [5, 3, 2, 1], 'Women' => [5, 3, 2, 1], 'Doubles' => [8, 6, 4, 2]];
    }
    if ($eventId === 41) {
        return ['Men' => $danceSport, 'Women' => $danceSport];
    }
    if ($eventId === 43) {
        return ['Men' => $common, 'Women' => $common];
    }
    return [];
}
function gold_award_values(int $eventId):array{
    if ($eventId === 33) {
        return [
            'MR. & MS. HCCCI' => 20,
            '1st Runner-Up' => 15,
            '2nd Runner-up' => 10,
            'Other Contestants' => 5,
            'Production Number' => 3,
            'School Uniform' => 3,
            'Preliminary Interview' => 3,
            'Sports Attire' => 3,
            'Formal Wear' => 3,
            'Advocacy Video' => 3,
            'Photogenic' => 3,
            'MR. & MS. Congeniality' => 3,
            'MR. & MS. Telegenic' => 3,
        ];
    }
    if($eventId!==34)return [];
    return ['HCCCI Top Model'=>15,'1st Runner-Up'=>12,'2nd Runner-Up'=>9,'Other Models'=>4,'Best in Production Number'=>2,'Best in White Fashion'=>2,'Best in Team-inspired Fashion'=>2,'Photogenic'=>2,'Best Runway Presence Award'=>1,'Most Stylish Award'=>1,'Iconic Hair and Makeup Award'=>1,'Innovative Accessory Showcase Award'=>1,'Effortless Elegance Award'=>1,"People's Choice Award"=>1,'Best Designer Award'=>5];
}
function gold_award_placement(int $eventId,string $award):?int{
    if ($eventId === 33) {
        return match (trim($award)) {
            'Mr. & Ms. HCCCI', 'MR. & MS. HCCCI' => 1,
            '1st Runner-Up' => 2,
            '2nd Runner-Up', '2nd Runner-up' => 3,
            'Other Contestants' => 4,
            default => null,
        };
    }
    if($eventId!==34)return null;return match($award){'HCCCI Top Model'=>1,'1st Runner-Up'=>2,'2nd Runner-Up'=>3,'Other Models'=>4,default=>null};
}
function gold_scale(int $eventId,string $division=''):array{
 $divisionScales=gold_scales($eventId);if($divisionScales!==[]){if($eventId===19&&$division==='Doubles')$division="Men's Doubles";if(isset($divisionScales[$division]))return $divisionScales[$division];if($eventId===43&&$division==='')return $divisionScales['Men'];return [];} 
 /* Placement gold values transcribed from the supplied 2026 IRR Gold System. */
 $m=[
 1=>[5,3,2,1],2=>[5,3,2,1],3=>[5,3,2,1],4=>[8,6,4,2],5=>[5,3,2,1],6=>[5,3,2,1],
 7=>[15,10,8,6],8=>[8,6,4,2],9=>[25,20,15,10],10=>[25,20,15,10],11=>[15,10,8,6],
 12=>[5,3,2,1],13=>[8,6,4,2],14=>[10,8,6,4],15=>[15,10,8,6],
 16=>[10,6,4,2],17=>[10,6,4,2],18=>[10,6,4,2],
 20=>[5,3,2,1],21=>[5,3,2,1],22=>[5,3,2,1],23=>[5,3,2,1],
 26=>[10,8,6,4],27=>[8,6,4,2],28=>[8,6,4,2],29=>[10,8,6,4],30=>[8,6,4,2],31=>[5,3,2,1],32=>[8,6,4,2],
 33=>[20,15,10,5],34=>[15,12,9,4],35=>[10,8,6,4],36=>[10,8,6,4],42=>[8,6,2,1],44=>[5,3,2,1]
 ];
 return $m[$eventId]??[];
}
function gold_rows():array{ensure_gold_schema();return db()->query('SELECT g.* FROM gold_awards g ORDER BY g.created_at DESC')->fetchAll();}
function gold_totals():array{
 ensure_gold_schema();$teams=['Water Tribe','Air Nomads','Fire Nation','Earth Kingdom'];$out=[];foreach($teams as $team)$out[$team]=['team'=>$team,'division'=>'Overall','total'=>0.0,'awards'=>0];
 $s=db()->query('SELECT team,COALESCE(SUM(gold_points),0) total,COUNT(*) awards FROM gold_awards GROUP BY team');foreach($s as $r)if(isset($out[$r['team']])){$out[$r['team']]['total']=(float)$r['total'];$out[$r['team']]['awards']=(int)$r['awards'];}
 $out=array_values($out);usort($out,fn($a,$b)=>($b['total']<=>$a['total'])?: (array_search($a['team'],$teams,true)<=>array_search($b['team'],$teams,true)));return $out;
}
