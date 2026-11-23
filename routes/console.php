<?php

use App\Services\WebPushService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('webpush:vapid {--force : Timpa kunci VAPID yang sudah ada di .env}', function (WebPushService $webPushService) {
    $envPath = base_path('.env');
    if (! is_file($envPath)) {
        $this->error('File .env tidak ditemukan.');

        return 1;
    }

    $envContent = (string) file_get_contents($envPath);
    $hasExisting = preg_match('/^VAPID_PUBLIC_KEY=\S+/m', $envContent)
        && preg_match('/^VAPID_PRIVATE_KEY=\S+/m', $envContent);

    if ($hasExisting && ! $this->option('force')) {
        $this->info('Kunci VAPID sudah terkonfigurasi di .env. Gunakan --force jika ingin membuat ulang.');

        return 0;
    }

    $keys = $webPushService->generateVapidKeyPair();
    if ($keys === null) {
        $this->error('Gagal membuat pasangan kunci ECDSA P-256 melalui OpenSSL.');

        return 1;
    }

    $updates = [
        'VAPID_SUBJECT' => env('VAPID_SUBJECT', 'mailto:admin@barantau.local'),
        'VAPID_PUBLIC_KEY' => $keys['public_key'],
        'VAPID_PRIVATE_KEY' => $keys['private_key'],
    ];

    foreach ($updates as $key => $val) {
        if (preg_match("/^{$key}=.*/m", $envContent)) {
            $envContent = (string) preg_replace("/^{$key}=.*/m", "{$key}={$val}", $envContent);
        } else {
            $envContent = rtrim($envContent) . PHP_EOL . "{$key}={$val}" . PHP_EOL;
        }
    }

    file_put_contents($envPath, $envContent);
    $this->info('Kunci VAPID_PUBLIC_KEY dan VAPID_PRIVATE_KEY berhasil disimpan ke file .env.');

    return 0;
})->purpose('Generate pasangan kunci VAPID Web Push dan simpan ke file .env');

Artisan::command('reverb:setup-env', function () {
    $envPath = base_path('.env');
    if (! is_file($envPath)) {
        $this->error('File .env tidak ditemukan.');

        return 1;
    }

    $envContent = (string) file_get_contents($envPath);

    $defaults = [
        'BROADCAST_CONNECTION' => 'reverb',
        'REVERB_APP_ID' => (string) random_int(100000, 999999),
        'REVERB_APP_KEY' => 'barantau-' . bin2hex(random_bytes(8)),
        'REVERB_APP_SECRET' => bin2hex(random_bytes(16)),
        'REVERB_HOST' => '"127.0.0.1"',
        'REVERB_PORT' => '8080',
        'REVERB_SCHEME' => 'http',
        'REVERB_SERVER_HOST' => '0.0.0.0',
        'REVERB_SERVER_PORT' => '8080',
    ];

    foreach ($defaults as $key => $defaultVal) {
        if ($key === 'BROADCAST_CONNECTION') {
            if (preg_match('/^BROADCAST_CONNECTION=.*/m', $envContent)) {
                $envContent = (string) preg_replace('/^BROADCAST_CONNECTION=.*/m', 'BROADCAST_CONNECTION=reverb', $envContent);
            } else {
                $envContent = rtrim($envContent) . PHP_EOL . 'BROADCAST_CONNECTION=reverb' . PHP_EOL;
            }
            continue;
        }

        if (! preg_match("/^{$key}=\\S+/m", $envContent)) {
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = (string) preg_replace("/^{$key}=.*/m", "{$key}={$defaultVal}", $envContent);
            } else {
                $envContent = rtrim($envContent) . PHP_EOL . "{$key}={$defaultVal}" . PHP_EOL;
            }
        }
    }

    file_put_contents($envPath, $envContent);
    $this->info('Konfigurasi Laravel Reverb berhasil disiapkan di .env.');

    return 0;
})->purpose('Siapkan variabel environment Laravel Reverb di .env');
