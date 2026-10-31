<?php
$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\{User, Organizer, Event, Ticket, Payment, WithdrawalRequest, Notification, GalleryPhoto};
$database = str_replace('\\', '/', (string) config('database.connections.'.config('database.default').'.database'));
if (!app()->environment(['local','testing']) || !(str_ends_with($database, '/database/browser.sqlite') || preg_match('/^sportmanado_(?:readiness_)?test(?:_|$)/', $database))) throw new RuntimeException('Use an isolated browser fixture database only.');
if (User::exists()) throw new RuntimeException('Fixture database must be empty. No existing data was deleted.');
$testPassword = getenv('SPORTMANADO_TEST_PASSWORD');
if (!is_string($testPassword) || strlen($testPassword)<12) throw new RuntimeException('Set SPORTMANADO_TEST_PASSWORD (12+ characters).');
$password = Illuminate\Support\Facades\Hash::make($testPassword);
$user = User::create(['name'=>str_repeat('Peserta Manado ', 4), 'email'=>'participant.with.a.long.address@example.test', 'password'=>$password, 'role'=>'user']);
$eo = User::create(['name'=>'Organizer Demo', 'email'=>'organizer@example.test', 'password'=>$password, 'role'=>'organizer']);
$admin = User::create(['name'=>'Admin Demo', 'email'=>'admin@example.test', 'password'=>$password, 'role'=>'admin']);
$organizer = Organizer::create(['user_id'=>$eo->id,'organization_name'=>'Komunitas Olahraga Manado Demonstrasi','phone'=>'081234567890','status'=>'approved']);
foreach ([['Gratis Otomatis',0,true],['Gratis Persetujuan',0,false],['Berbayar Sandbox',25000,true]] as [$title,$price,$auto]) {
    $event = Event::create(['organizer_id'=>$organizer->id,'title'=>$title.' Kegiatan Komunitas SportManado','description'=>str_repeat('Kegiatan olahraga bersama komunitas. ',10),'location'=>'Lapangan Olahraga Kawasan Megamas Kota Manado Sulawesi Utara','event_date'=>now()->addDays(30),'registration_deadline'=>now()->addDays(20),'max_participants'=>30,'ticket_price'=>$price,'category'=>'Futsal','status'=>'active','auto_approve'=>$auto,'custom_fields'=>[['label'=>'Nomor WhatsApp','type'=>'text','required'=>false]]]);
    if ($price===0 && $auto) { Ticket::create(['user_id'=>$user->id,'event_id'=>$event->id,'ticket_code'=>'READINESS01','status'=>'active','custom_answers'=>['Nomor WhatsApp'=>'081234567890']]); }
    if ($price>0) {
        $ticket=Ticket::create(['user_id'=>$user->id,'event_id'=>$event->id,'ticket_code'=>'READINESS02','status'=>'active']);
        Payment::create(['user_id'=>$user->id,'ticket_id'=>$ticket->id,'amount'=>$price,'method'=>'ewallet','status'=>'confirmed']);
        WithdrawalRequest::create(['organizer_id'=>$organizer->id,'event_id'=>$event->id,'amount'=>10000,'bank_name'=>'Bank Demo','account_number'=>'1234567890','account_holder'=>'Organizer Demo','status'=>'rejected','admin_notes'=>str_repeat('Catatan pengujian penarikan yang panjang. ',8)]);
    }
}
Notification::create(['user_id'=>$eo->id,'type'=>'event_approved','title'=>'Kegiatan disetujui','message'=>'Notifikasi demonstrasi untuk pengujian tampilan.','is_read'=>false]);
echo "Browser fixtures ready\n";
