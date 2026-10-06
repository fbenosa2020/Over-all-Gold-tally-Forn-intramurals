<?php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(preg_match('#/(storage|data)/#',$path)){http_response_code(403);exit('Forbidden');}
if($path!=='/'&&is_file(__DIR__.$path))return false;
require __DIR__.'/index.php';
