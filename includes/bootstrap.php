<?php
session_start();
require_once __DIR__.'/../config/database.php';
function h($s): string { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
function csrf(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function verify_csrf(): void { if(!hash_equals(csrf(),(string)($_POST['csrf']??''))) {http_response_code(403);exit('Invalid security token');} }
function go(string $page): void { header('Location: index.php?page='.rawurlencode($page));exit; }
function poststr(string $key,int $max=160): string { $v=trim((string)($_POST[$key]??'')); if($v==='' || mb_strlen($v)>$max) throw new RuntimeException('Invalid '.str_replace('_',' ',$key)); return $v; }
function postint(string $key): int { $v=filter_var($_POST[$key]??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]); if($v===false) throw new RuntimeException('Invalid '.str_replace('_',' ',$key)); return $v; }
function validdate(string $v): bool { $d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);return $d && $d->format('Y-m-d')===$v; }
function datefield(string $key): string { $v=poststr($key,10);if(!validdate($v))throw new RuntimeException('Invalid date');return $v; }
function timefield(string $key): string { $v=poststr($key,5);if(!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/',$v))throw new RuntimeException('Invalid time');return $v; }
function moneyfield(string $key): string { $v=poststr($key,12);if(!preg_match('/^\d{1,8}(\.\d{1,2})?$/',$v) || (float)$v<=0)throw new RuntimeException('Invalid amount');return $v; }
function rows(PDO $db,string $sql): array {return $db->query($sql)->fetchAll();}
function options(array $rows,string $label='full_name'): void {foreach($rows as $r) echo '<option value="'.h($r['id']).'">'.h($r[$label]).'</option>';}
