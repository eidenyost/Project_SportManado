<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;
use RuntimeException;

class MigrateStorage extends Command
{
    protected $signature = 'storage:migrate {source : Snapshot folder containing public and private folders}
        {--apply : Upload missing files; otherwise only report}
        {--report= : New JSON report path outside the web root}';
    protected $description = 'Copy a storage snapshot without overwriting different files; verify SHA-256 after upload';

    public function handle(): int
    {
        $source = realpath($this->argument('source'));
        if (!$source || !is_dir($source.'/public') || !is_dir($source.'/private')) {
            $this->error('Source must contain both public and private folders.');
            return self::FAILURE;
        }
        $report = $this->option('report');
        if ($report && (file_exists($report) || !is_dir(dirname($report))
            || str_starts_with(str_replace('\\', '/', realpath(dirname($report))).'/', str_replace('\\', '/', realpath(public_path())).'/'))) {
            $this->error('Report must be a new file outside the public web root.');
            return self::FAILURE;
        }
        $results = [];
        try {
            foreach (['public' => 'public', 'private' => 'local'] as $folder => $diskName) {
                $base = $source.'/'.$folder;
                $disk = Storage::disk($diskName);
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)) as $file) {
                    if (!$file->isFile() || $file->isLink() || str_starts_with($file->getFilename(), '.')) continue;
                    $resolved = realpath($file->getPathname());
                    if (!$resolved || !str_starts_with(str_replace('\\', '/', $resolved), str_replace('\\', '/', realpath($base)).'/')) {
                        throw new RuntimeException('A source file escapes the snapshot folder.');
                    }
                    $path = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
                    $hash = hash_file('sha256', $resolved);
                    $exists = $disk->exists($path);
                    if ($exists) {
                        if ($this->remoteHash($disk, $path) !== $hash) throw new RuntimeException('Destination file differs; nothing was overwritten.');
                        $status = 'verified-existing';
                    } elseif (!$this->option('apply')) {
                        $status = 'would-upload';
                    } else {
                        $stream = fopen($resolved, 'rb');
                        try {
                            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($resolved) ?: 'application/octet-stream';
                            if (!$disk->put($path, $stream, ['ContentType' => $mime])) throw new RuntimeException('Upload failed.');
                        } finally {
                            if (is_resource($stream)) fclose($stream);
                        }
                        if ($this->remoteHash($disk, $path) !== $hash) throw new RuntimeException('Uploaded file checksum does not match.');
                        $status = 'uploaded-and-verified';
                    }
                    $results[] = ['disk' => $diskName, 'path' => $path, 'bytes' => $file->getSize(), 'sha256' => $hash, 'status' => $status];
                }
            }
            if ($report) file_put_contents($report, json_encode(['apply' => (bool) $this->option('apply'), 'passed' => true, 'files' => $results], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            $this->info(count($results).' files checked. Database and source files were not modified.');
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            if ($report) file_put_contents($report, json_encode(['apply' => (bool) $this->option('apply'), 'passed' => false, 'files' => $results], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            $this->error('Storage migration stopped. Check connectivity, permissions and file checksums; credentials were not printed.');
            return self::FAILURE;
        }
    }

    private function remoteHash($disk, string $path): string
    {
        $stream = $disk->readStream($path);
        if (!is_resource($stream)) throw new RuntimeException('Destination could not be read.');
        try {
            $hash = hash_init('sha256');
            hash_update_stream($hash, $stream);
            return hash_final($hash);
        } finally {
            fclose($stream);
        }
    }
}
