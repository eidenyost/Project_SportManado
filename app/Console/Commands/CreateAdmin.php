<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin';
    protected $description = 'Interactively create an administrator without a default password';

    public function handle(): int
    {
        $data = ['name'=>$this->ask('Nama admin'), 'email'=>$this->ask('Email admin'),
            'password'=>$this->secret('Password (minimal 12 karakter)'),
            'password_confirmation'=>$this->secret('Ulangi password')];
        $data['email'] = strtolower(trim((string) $data['email']));
        $validator = Validator::make($data, ['name'=>'required|string|max:255',
            'email'=>'required|email|max:255|unique:users,email','password'=>'required|string|min:12|confirmed']);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) $this->error($error);
            return self::FAILURE;
        }
        User::create(['name'=>$data['name'],'email'=>strtolower($data['email']),
            'password'=>Hash::make($data['password']),'role'=>'admin']);
        $this->info('Akun admin berhasil dibuat. Password tidak disimpan dalam log perintah.');
        return self::SUCCESS;
    }
}
