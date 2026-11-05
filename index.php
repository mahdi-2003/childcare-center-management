<?php
require __DIR__.'/includes/bootstrap.php';
$pages=['dashboard'=>'Dashboard','enrollment'=>'Enrollment','attendance'=>'Attendance','billing'=>'Billing','staff'=>'Staff & Shifts','classrooms'=>'Classrooms'];
$page=$_GET['page']??'dashboard';if(!isset($pages[$page]))$page='dashboard';
$error='';$success=isset($_GET['ok'])?'Saved successfully.':'';
if($_SERVER['REQUEST_METHOD']==='POST') {
 try {
  verify_csrf();$action=poststr('action',30);
  if($action==='guardian') {
   $email=poststr('email');if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Invalid email');
   $stmt=$pdo->prepare('INSERT INTO guardians(full_name,email,phone) VALUES(?,?,?)');$stmt->execute([poststr('full_name'),$email,poststr('phone',30)]);
  } elseif($action==='child') {
   $class=postint('classroom_id');$guardian=postint('guardian_id');$dob=datefield('birth_date');$enrolled=datefield('enrolled_on');
   if($dob>$enrolled)throw new RuntimeException('Birth date cannot be after enrollment');
   $pdo->beginTransaction();
   $lock=$pdo->prepare('SELECT capacity FROM classrooms WHERE id=? FOR UPDATE');$lock->execute([$class]);$capacity=$lock->fetchColumn();if($capacity===false)throw new RuntimeException('Classroom not found');
   $count=$pdo->prepare("SELECT COUNT(*) FROM children WHERE classroom_id=? AND status='active'");$count->execute([$class]);if((int)$count->fetchColumn()>=(int)$capacity)throw new RuntimeException('Classroom is full');
   $stmt=$pdo->prepare('INSERT INTO children(full_name,birth_date,classroom_id,enrolled_on) VALUES(?,?,?,?)');$stmt->execute([poststr('full_name'),$dob,$class,$enrolled]);
   $stmt=$pdo->prepare('INSERT INTO child_guardians(child_id,guardian_id,relationship) VALUES(?,?,?)');$stmt->execute([$pdo->lastInsertId(),$guardian,poststr('relationship',40)]);$pdo->commit();
  } elseif($action==='attendance') {
   $child=postint('child_id');$date=datefield('attendance_date');$in=timefield('check_in');$out=trim((string)($_POST['check_out']??''));if($out!==''){$out=timefield('check_out');if($out<$in)throw new RuntimeException('Checkout must follow check-in');}
   $stmt=$pdo->prepare('INSERT INTO attendance(child_id,attendance_date,check_in,check_out) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE check_in=VALUES(check_in),check_out=VALUES(check_out)');$stmt->execute([$child,$date,$in,$out?:null]);
  } elseif($action==='invoice') {
   $stmt=$pdo->prepare('INSERT INTO invoices(child_id,due_date,amount,description) VALUES(?,?,?,?)');$stmt->execute([postint('child_id'),datefield('due_date'),moneyfield('amount'),poststr('description',255)]);
  } elseif($action==='payment') {
   $id=postint('invoice_id');$amount=moneyfield('amount');$pdo->beginTransaction();
   $q=$pdo->prepare('SELECT amount FROM invoices WHERE id=? FOR UPDATE');$q->execute([$id]);$total=$q->fetchColumn();if($total===false)throw new RuntimeException('Invoice not found');
   $q=$pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id=?');$q->execute([$id]);$paid=$q->fetchColumn();
   if(round((float)$paid+(float)$amount,2)>round((float)$total,2))throw new RuntimeException('Payment exceeds outstanding balance');
   $q=$pdo->prepare('INSERT INTO payments(invoice_id,paid_on,amount) VALUES(?,?,?)');$q->execute([$id,datefield('paid_on'),$amount]);$pdo->commit();
  } elseif($action==='staff') {
   $email=poststr('email');if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Invalid email');
   $stmt=$pdo->prepare('INSERT INTO staff(full_name,email,role) VALUES(?,?,?)');$stmt->execute([poststr('full_name'),$email,poststr('role',70)]);
  } elseif($action==='shift') {
   $start=timefield('start_time');$end=timefield('end_time');if($end<=$start)throw new RuntimeException('Shift must end after it starts');
   $stmt=$pdo->prepare('INSERT INTO shifts(staff_id,classroom_id,shift_date,start_time,end_time) VALUES(?,?,?,?,?)');$stmt->execute([postint('staff_id'),postint('classroom_id'),datefield('shift_date'),$start,$end]);
  } elseif($action==='classroom') {
   $capacity=postint('capacity');if($capacity>100)throw new RuntimeException('Capacity too high');
   $stmt=$pdo->prepare('INSERT INTO classrooms(name,capacity,age_group) VALUES(?,?,?)');$stmt->execute([poststr('name',80),$capacity,poststr('age_group',60)]);
  } else throw new RuntimeException('Unknown action');
  header('Location: index.php?page='.rawurlencode($page).'&ok=1');exit;
 } catch(Throwable $e) {if($pdo->inTransaction())$pdo->rollBack();$error=($e instanceof PDOException)?'Could not save: check duplicates or references.':$e->getMessage();}
}
$classrooms=rows($pdo,'SELECT cl.id,cl.name,cl.age_group,cl.capacity,COUNT(c.id) enrolled FROM classrooms cl LEFT JOIN children c ON c.classroom_id=cl.id AND c.status="active" GROUP BY cl.id,cl.name,cl.age_group,cl.capacity ORDER BY cl.name');
$children=rows($pdo,"SELECT c.id,c.full_name,c.birth_date,c.enrolled_on,cl.name classroom,GROUP_CONCAT(CONCAT(g.full_name,' (',cg.relationship,')') SEPARATOR ', ') guardians FROM children c JOIN classrooms cl ON cl.id=c.classroom_id LEFT JOIN child_guardians cg ON cg.child_id=c.id LEFT JOIN guardians g ON g.id=cg.guardian_id GROUP BY c.id,c.full_name,c.birth_date,c.enrolled_on,cl.name ORDER BY c.id DESC");
$guardians=rows($pdo,'SELECT * FROM guardians ORDER BY full_name');$staff=rows($pdo,'SELECT * FROM staff ORDER BY full_name');
$attend=rows($pdo,'SELECT a.attendance_date,c.full_name child,cl.name classroom,a.check_in,a.check_out FROM attendance a JOIN children c ON c.id=a.child_id JOIN classrooms cl ON cl.id=c.classroom_id ORDER BY a.attendance_date DESC,a.id DESC LIMIT 40');
$invoices=rows($pdo,'SELECT i.id,c.full_name child,i.due_date,i.description,i.amount,COALESCE(SUM(p.amount),0) paid,i.amount-COALESCE(SUM(p.amount),0) balance FROM invoices i JOIN children c ON c.id=i.child_id LEFT JOIN payments p ON p.invoice_id=i.id GROUP BY i.id,c.full_name,i.due_date,i.description,i.amount ORDER BY i.id DESC');
$shifts=rows($pdo,'SELECT sh.shift_date,s.full_name staff,cl.name classroom,sh.start_time,sh.end_time FROM shifts sh JOIN staff s ON s.id=sh.staff_id JOIN classrooms cl ON cl.id=sh.classroom_id ORDER BY sh.shift_date DESC,sh.start_time LIMIT 40');
function formstart(string $action): void {echo '<form method="post"><input type="hidden" name="csrf" value="'.h(csrf()).'"><input type="hidden" name="action" value="'.h($action).'">';}
function field(string $name,string $label,string $type='text',string $extra=''): void {echo '<div><label for="'.h($name).'">'.h($label).'</label><input required id="'.h($name).'" name="'.h($name).'" type="'.h($type).'" '. $extra .'></div>';}
function dropdown(string $name,string $label,array $data,string $col='full_name'): void {echo '<div><label for="'.h($name).'">'.h($label).'</label><select required id="'.h($name).'" name="'.h($name).'">';options($data,$col);echo '</select></div>';}
function tableview(array $data,array $cols): void {echo '<div class="table-wrap"><table><thead><tr>';foreach($cols as $label)echo '<th>'.h($label).'</th>';echo '</tr></thead><tbody>';foreach($data as $r){echo '<tr>';foreach($cols as $key=>$label)echo '<td>'.h($r[$key]??'—').'</td>';echo '</tr>';}if(!$data)echo '<tr><td colspan="'.count($cols).'">No records yet</td></tr>';echo '</tbody></table></div>';}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=h($pages[$page])?> | BrightStart Childcare</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header><h1>☀ BrightStart Childcare</h1><p>Center Management Dashboard · CIS 344 Project</p></header><nav aria-label="Main navigation"><?php foreach($pages as $slug=>$name):?><a class="<?=$slug===$page?'active':''?>" href="?page=<?=h($slug)?>"><?=h($name)?></a><?php endforeach;?></nav>
<main><h2><?=h($pages[$page])?></h2><?php if($error):?><p class="error" role="alert"><?=h($error)?></p><?php endif;?><?php if($success):?><p class="success" role="status"><?=h($success)?></p><?php endif;?>
<?php if($page==='dashboard'):?>
<div class="grid"><section class="card"><div class="muted">Enrolled children</div><div class="stat"><?=count($children)?></div></section><section class="card"><div class="muted">Classrooms</div><div class="stat"><?=count($classrooms)?></div></section><section class="card"><div class="muted">Staff members</div><div class="stat"><?=count($staff)?></div></section><section class="card"><div class="muted">Outstanding invoices ($)</div><div class="stat"><?=h(number_format(array_sum(array_column($invoices,'balance')),2))?></div></section></div>
<section class="card"><h3>Classroom capacity overview</h3><?php tableview($classrooms,['name'=>'Classroom','age_group'=>'Age group','capacity'=>'Capacity','enrolled'=>'Active enrollment']);?></section>
<?php elseif($page==='classrooms'):?>
<section class="card"><h3>Add classroom</h3><?php formstart('classroom');?><div class="form-grid"><?php field('name','Classroom name');field('age_group','Age group');field('capacity','Maximum children','number','min="1" max="100"');?></div><button>Add classroom</button></form></section><section class="card"><h3>Capacity report</h3><?php tableview($classrooms,['name'=>'Classroom','age_group'=>'Age group','capacity'=>'Capacity','enrolled'=>'Enrolled']);?></section>
<?php elseif($page==='enrollment'):?>
<section class="card"><h3>Step 1 · Add guardian</h3><?php formstart('guardian');?><div class="form-grid"><?php field('full_name','Guardian name');field('email','Email','email');field('phone','Phone','tel');?></div><button>Save guardian</button></form></section>
<section class="card"><h3>Step 2 · Enroll child</h3><?php formstart('child');?><div class="form-grid"><?php field('full_name','Child name');field('birth_date','Date of birth','date');field('enrolled_on','Enrollment date','date','value="'.date('Y-m-d').'"');dropdown('classroom_id','Classroom',$classrooms,'name');dropdown('guardian_id','Guardian',$guardians);field('relationship','Relationship (e.g., Parent)');?></div><button>Enroll child</button></form></section><section class="card"><h3>Children and guardians (SQL joins)</h3><?php tableview($children,['full_name'=>'Child','birth_date'=>'Birth date','classroom'=>'Classroom','guardians'=>'Guardians','enrolled_on'=>'Enrolled']);?></section>
<?php elseif($page==='attendance'):?>
<section class="card"><h3>Check in / update check out</h3><?php formstart('attendance');?><div class="form-grid"><?php dropdown('child_id','Child',$children);field('attendance_date','Date','date','value="'.date('Y-m-d').'"');field('check_in','Check in','time');echo '<div><label for="check_out">Check out (optional)</label><input id="check_out" name="check_out" type="time"></div>';?></div><button>Save attendance</button></form></section><section class="card"><h3>Attendance report</h3><?php tableview($attend,['attendance_date'=>'Date','child'=>'Child','classroom'=>'Classroom','check_in'=>'Check in','check_out'=>'Check out']);?></section>
<?php elseif($page==='billing'):?>
<section class="card"><h3>Create invoice</h3><?php formstart('invoice');?><div class="form-grid"><?php dropdown('child_id','Child',$children);field('due_date','Due date','date');field('amount','Amount ($)','number','min="0.01" step="0.01"');field('description','Description');?></div><button>Create invoice</button></form></section>
<section class="card"><h3>Record payment</h3><?php formstart('payment');?><div class="form-grid"><?php echo '<div><label for="invoice_id">Invoice</label><select id="invoice_id" name="invoice_id" required>';foreach($invoices as $i)if((float)$i['balance']>0)echo '<option value="'.h($i['id']).'">#'.h($i['id']).' — '.h($i['child']).' ($'.h($i['balance']).' due)</option>';echo '</select></div>';field('paid_on','Payment date','date','value="'.date('Y-m-d').'"');field('amount','Payment amount ($)','number','min="0.01" step="0.01"');?></div><button>Record payment</button></form></section>
<section class="card"><h3>Invoice balances (LEFT JOIN + SUM)</h3><?php tableview($invoices,['id'=>'Invoice #','child'=>'Child','due_date'=>'Due','description'=>'Description','amount'=>'Amount ($)','paid'=>'Paid ($)','balance'=>'Balance ($)']);?></section>
<?php elseif($page==='staff'):?>
<section class="card"><h3>Add staff member</h3><?php formstart('staff');?><div class="form-grid"><?php field('full_name','Staff name');field('email','Email','email');field('role','Role');?></div><button>Add staff</button></form></section>
<section class="card"><h3>Schedule shift</h3><?php formstart('shift');?><div class="form-grid"><?php dropdown('staff_id','Staff member',$staff);dropdown('classroom_id','Classroom',$classrooms,'name');field('shift_date','Date','date');field('start_time','Start time','time');field('end_time','End time','time');?></div><button>Schedule shift</button></form></section>
<section class="card"><h3>Staff shifts (SQL joins)</h3><?php tableview($shifts,['shift_date'=>'Date','staff'=>'Staff member','classroom'=>'Classroom','start_time'=>'Start','end_time'=>'End']);?></section>
<?php endif;?></main><footer>BrightStart · Academic demonstration · Use fictional data only</footer></body></html>
