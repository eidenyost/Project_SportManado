<?php

// This fixture test intentionally creates rows ONLY in a dedicated test database.
$root = dirname(__DIR__);
$configuration = $argv[1] ?? $root.'/phpunit.mysql.local.xml';
$xml = simplexml_load_file($configuration);
foreach ($xml->php->env as $entry) {
    $name=(string)$entry['name']; $value=(string)$entry['value'];
    putenv($name.'='.$value); $_ENV[$name]=$value; $_SERVER[$name]=$value;
}
require __DIR__.'/bootstrap.php';
if (getenv('DB_CONNECTION') !== 'mysql') throw new RuntimeException('This test requires an isolated MySQL database.');
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\{User, Organizer, Event, Ticket};
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

$mode=$argv[2] ?? 'parent';
if ($mode!=='parent') {
    $deadline=microtime(true)+15;
    while (!is_file($argv[6].'/start')) {
        if(microtime(true)>$deadline) throw new RuntimeException('Concurrency barrier timed out.');
        usleep(10000);
    }
    try {
        Auth::loginUsingId((int)$argv[4]);
        $event=Event::findOrFail((int)$argv[3]);
        $request=Request::create('/test','POST',['ticket_code'=>$argv[5]],[],[],['HTTP_ACCEPT'=>'application/json']);
        $controller=app(TicketController::class);
        $response=$mode==='register' ? $controller->submitPayment($request,$event) : $controller->validateQr($request,$event);
        echo json_encode(['status'=>$response->getStatusCode()]);
    } catch (Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception) {
        echo json_encode(['status'=>$exception->getStatusCode()]);
    } catch (Throwable $exception) {
        echo json_encode(['status'=>500,'exception'=>get_class($exception)]);
    }
    exit;
}

$tag=bin2hex(random_bytes(6));
$owner=User::create(['name'=>'Concurrent Organizer','email'=>"eo.$tag@example.test",'password'=>bcrypt('test-password'),'role'=>'organizer']);
$organizer=Organizer::create(['user_id'=>$owner->id,'organization_name'=>'Concurrency Fixture','status'=>'approved']);
$event=Event::create(['organizer_id'=>$organizer->id,'title'=>'Last Slot '.$tag,'description'=>'Test fixture','location'=>'Manado',
    'event_date'=>now()->addDays(10),'registration_deadline'=>now()->addDays(5),'max_participants'=>1,
    'ticket_price'=>0,'category'=>'Futsal','status'=>'active','auto_approve'=>true]);
$users=[];
foreach ([1,2] as $index) $users[]=User::create(['name'=>'Concurrent Participant','email'=>"p$index.$tag@example.test",'password'=>bcrypt('test-password'),'role'=>'user']);

function race(string $mode, array $userIds, int $eventId, string $code, string $configuration): array {
    $barrier=storage_path('framework/testing/concurrency-'.bin2hex(random_bytes(6)));
    mkdir($barrier,0775,true);
    $workers=[];
    foreach ($userIds as $userId) {
        $process=proc_open([PHP_BINARY,__FILE__,$configuration,$mode,(string)$eventId,(string)$userId,$code,$barrier],
            [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if(!is_resource($process)) throw new RuntimeException('Could not start test worker.');
        fclose($pipes[0]); $workers[]=[$process,$pipes];
    }
    file_put_contents($barrier.'/start','ready');
    $results=[];
    foreach ($workers as [$process,$pipes]) {
        $result=json_decode(stream_get_contents($pipes[1]),true);
        $stderr=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
        $exit=proc_close($process);
        if($exit!==0 || !is_array($result)) throw new RuntimeException('Concurrency worker failed; inspect isolated test environment.');
        $results[]=$result['status'];
    }
    sort($results); return $results;
}

$registration=race('register',array_map(fn($user)=>$user->id,$users),$event->id,'',$configuration);
if($registration!==[302,422] || Ticket::where('event_id',$event->id)->count()!==1) throw new RuntimeException('Last-slot registration race failed.');
$ticket=Ticket::where('event_id',$event->id)->firstOrFail();
$scan=race('scan',[$owner->id,$owner->id],$event->id,$ticket->ticket_code,$configuration);
if($scan!==[200,422] || $ticket->fresh()->status!=='used') throw new RuntimeException('One-time scanner race failed.');
echo json_encode(['registration_statuses'=>$registration,'ticket_count'=>1,'scan_statuses'=>$scan,'ticket_status'=>$ticket->fresh()->status],JSON_PRETTY_PRINT).PHP_EOL;
