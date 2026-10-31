<?php
// Real concurrent workers, limited to the guarded isolated MySQL test database.
$root=dirname(__DIR__);$configuration=$argv[1]??$root.'/phpunit.mysql.local.xml';
$xml=simplexml_load_file($configuration);
foreach($xml->php->env as $entry){$name=(string)$entry['name'];$value=(string)$entry['value'];putenv($name.'='.$value);$_ENV[$name]=$value;$_SERVER[$name]=$value;}
require __DIR__.'/bootstrap.php';
if(getenv('DB_CONNECTION')!=='mysql') throw new RuntimeException('Isolated MySQL required.');
$app=require $root.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['services.midtrans.is_production'=>false]);
use App\Models\{User, Organizer, Event, Ticket, Payment, WithdrawalRequest, Notification};
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
$mode=$argv[2]??'parent';
if($mode!=='parent'){
 $deadline=microtime(true)+15;while(!is_file($argv[5].'/start')){if(microtime(true)>$deadline)throw new RuntimeException('Barrier timeout.');usleep(10000);}
 try{
  Auth::loginUsingId((int)$argv[4]);
  $request=Request::create('/test','POST',['amount'=>75000,'bank_name'=>'BCA','account_number'=>'1234567890','account_holder'=>'Concurrent EO','admin_notes'=>'Uji perlombaan'],[],['transfer_proof'=>\Tests\Support\TransferProof::image()],['HTTP_ACCEPT'=>'application/json']);
  $request->setUserResolver(fn()=>Auth::user());
  if($mode==='submit') $response=app(App\Http\Controllers\OrganizerFinanceController::class)->withdraw(Event::findOrFail($argv[3]),$request);
  elseif($mode==='approve') $response=app(App\Http\Controllers\AdminDashboardController::class)->approveWithdrawal($request,WithdrawalRequest::findOrFail($argv[3]));
  else $response=app(App\Http\Controllers\AdminDashboardController::class)->rejectWithdrawal($request,WithdrawalRequest::findOrFail($argv[3]));
  echo json_encode(['status'=>$response->getStatusCode()]);
 }catch(Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e){echo json_encode(['status'=>$e->getStatusCode()]);}
 catch(Illuminate\Validation\ValidationException $e){echo json_encode(['status'=>422]);}
 catch(Throwable $e){echo json_encode(['status'=>500,'exception'=>get_class($e),'message'=>$e->getMessage()]);}
 exit;
}
function raceFinance(array $modes,int $id,int $userId,string $configuration):array{
 $barrier=storage_path('framework/testing/finance-'.bin2hex(random_bytes(6)));mkdir($barrier,0775,true);$workers=[];
 foreach($modes as $mode){$process=proc_open([PHP_BINARY,__FILE__,$configuration,$mode,(string)$id,(string)$userId,$barrier],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($process))throw new RuntimeException('Worker creation failed.');fclose($pipes[0]);$workers[]=[$process,$pipes];}
 file_put_contents($barrier.'/start','go');$results=[];
 foreach($workers as [$process,$pipes]){$result=json_decode(stream_get_contents($pipes[1]),true);fclose($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[2]);$exit=proc_close($process);if($exit!==0||!is_array($result)||$result['status']===500)throw new RuntimeException('Finance worker failed: '.json_encode($result));$results[]=$result['status'];}
 sort($results);return $results;
}
$tag=bin2hex(random_bytes(5));
$owner=User::create(['name'=>'Finance EO','email'=>'finance.'.$tag.'@example.test','password'=>bcrypt('test-password'),'role'=>'organizer']);
$organizer=Organizer::create(['user_id'=>$owner->id,'organization_name'=>'Concurrent Finance','status'=>'approved']);
$admin=User::create(['name'=>'Admin','email'=>'admin.'.$tag.'@example.test','password'=>bcrypt('test-password'),'role'=>'admin']);
$event=Event::create(['organizer_id'=>$organizer->id,'title'=>'Race finance','description'=>'Test','location'=>'Manado','event_date'=>now()->addDays(10),'registration_deadline'=>now()->addDays(5),'ticket_price'=>100000,'max_participants'=>10,'category'=>'Futsal','status'=>'active']);
$payer=User::create(['name'=>'Payer','email'=>'payer.'.$tag.'@example.test','password'=>bcrypt('test-password'),'role'=>'user']);
$ticket=Ticket::create(['user_id'=>$payer->id,'event_id'=>$event->id,'ticket_code'=>'FIN-'.$tag,'status'=>'pending']);
Payment::create(['user_id'=>$payer->id,'ticket_id'=>$ticket->id,'amount'=>100000,'method'=>'ewallet','status'=>'confirmed']);
$submission=raceFinance(['submit','submit'],$event->id,$owner->id,$configuration);
$finance=app(FinanceService::class);
if(WithdrawalRequest::where('event_id',$event->id)->count()!==1||$finance->availableBalance($event)!==25000)throw new RuntimeException('Concurrent submissions over-reserved the balance.');
$withdrawal=WithdrawalRequest::where('event_id',$event->id)->firstOrFail();
$decisions=raceFinance(['approve','reject'],$withdrawal->id,$admin->id,$configuration);
$state=$withdrawal->fresh()->status;$expected=$state==='approved'?25000:100000;
$notices=Notification::where('notifiable_type',WithdrawalRequest::class)->where('notifiable_id',$withdrawal->id)->whereIn('type',['withdrawal_approved','withdrawal_rejected'])->count();
if($decisions!==[302,400]||$finance->availableBalance($event)!==$expected||$notices!==1)throw new RuntimeException('Concurrent decisions changed balance or notification twice.');
echo json_encode(['submission_statuses'=>$submission,'reservation_count'=>1,'decision_statuses'=>$decisions,'final_status'=>$state,'available_balance'=>$expected,'decision_notifications'=>$notices],JSON_PRETTY_PRINT).PHP_EOL;
