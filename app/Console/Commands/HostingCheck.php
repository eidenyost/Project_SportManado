<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HostingCheck extends Command
{
    protected $signature = 'hosting:check {--database : Check database connectivity and required tables}';
    protected $description = 'Read-only checks for SportManado hosting; never prints credentials';

    public function handle(): int
    {
        $checks = [];
        $check = function (string $name, bool $passed) use (&$checks): void {
            $checks[] = [$passed ? 'PASS' : 'FAIL', $name];
        };
        $check('PHP >= 8.2', PHP_VERSION_ID >= 80200);
        foreach (['ctype','curl','dom','fileinfo','filter','hash','iconv','json','libxml','mbstring','openssl','pcre','PDO','session','tokenizer','xmlwriter'] as $extension) {
            $check('Extension '.$extension, extension_loaded($extension));
        }
        $check('APP_ENV=production', app()->environment('production'));
        $check('APP_DEBUG=false', !config('app.debug'));
        $key = config('app.key');
        $decoded = is_string($key) && str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;
        $check('APP_KEY valid for AES-256-CBC', is_string($decoded) && strlen($decoded) === 32);
        $host = parse_url(config('app.url'), PHP_URL_HOST);
        $check('APP_URL uses public HTTPS', str_starts_with(config('app.url'), 'https://')
            && is_string($host) && !in_array($host, ['localhost','127.0.0.1','::1'], true));
        $check('Secure session cookie', (bool) config('session.secure'));
        $check('Timezone Asia/Makassar (WITA)', config('app.timezone') === 'Asia/Makassar');
        $check('Local payment simulator disabled', !config('services.midtrans.demo'));
        $check('Session driver database', config('session.driver') === 'database');
        $check('Queue sync (no persistent worker needed)', config('queue.default') === 'sync');
        foreach (['storage','storage/app/private','storage/app/public','storage/framework/views','storage/framework/sessions','bootstrap/cache'] as $directory) {
            $check('Writable '.$directory, is_dir(base_path($directory)) && is_writable(base_path($directory)));
        }
        $check('public/storage points to public disk only', realpath(public_path('storage')) !== false
            && realpath(public_path('storage')) === realpath(storage_path('app/public')));
        foreach (['app.css','alpine.min.js','lucide.min.js','sidebar.js','forms.js','flatpickr.min.js','flatpickr.min.css','flatpickr-id.js',
            'fonts/inter-latin-wght-normal.woff2','fonts/archivo-latin-wght-normal.woff2','fonts/material-symbols-outlined-latin-wght-normal.woff2'] as $asset) {
            $check('Local asset '.$asset, is_file(public_path('assets/'.$asset)));
        }
        $check('Midtrans key pair configured', filled(config('services.midtrans.server_key')) && filled(config('services.midtrans.client_key')));
        $check('SMTP configured for password reset', config('mail.default') === 'smtp'
            && filled(config('mail.mailers.smtp.host')) && filled(config('mail.from.address')));
        if ($this->option('database')) {
            try {
                DB::select('SELECT 1');
                $check('Database reachable', true);
                foreach (['migrations','users','sessions','cache','cache_locks','organizers','events','tickets','payments','notifications','gallery_photos','event_registration_requests','withdrawal_requests'] as $table) {
                    $check('Table '.$table, Schema::hasTable($table));
                }
                if (DB::getDriverName() === 'mysql') {
                    foreach (DB::select('SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = ?', ['BASE TABLE']) as $table) {
                        $check('Transactional table '.$table->TABLE_NAME, strtolower($table->ENGINE) === 'innodb');
                    }
                }
            } catch (\Throwable $exception) {
                $check('Database checks (verify connection and schema settings)', false);
            }
        }
        $this->table(['Result', 'Check'], $checks);
        $this->line('Separate acceptance tests required: web document root, HTTPS, SMTP delivery, Midtrans webhook, scanner, scheduler and backup restore.');
        return collect($checks)->contains(fn ($row) => $row[0] === 'FAIL') ? self::FAILURE : self::SUCCESS;
    }
}
