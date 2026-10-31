<?php

$mediaDriver = env('MEDIA_STORAGE_DRIVER', 'local');
if (!in_array($mediaDriver, ['local', 's3'], true)) {
    throw new InvalidArgumentException('MEDIA_STORAGE_DRIVER must be local or s3.');
}
$remoteMedia = [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true),
    'visibility' => 'private',
    'throw' => true,
    'report' => false,
    'http' => ['connect_timeout' => 5, 'timeout' => 30],
    'request_checksum_calculation' => 'when_required',
    'response_checksum_validation' => 'when_required',
];
if ($mediaDriver === 's3') {
    foreach (['AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY', 'AWS_DEFAULT_REGION', 'AWS_ENDPOINT',
        'AWS_PUBLIC_BUCKET', 'AWS_PRIVATE_BUCKET', 'AWS_PUBLIC_URL'] as $required) {
        if (!is_string(env($required)) || trim(env($required)) === '') {
            throw new InvalidArgumentException('Missing remote storage configuration: '.$required);
        }
    }
    if (env('AWS_PUBLIC_BUCKET') === env('AWS_PRIVATE_BUCKET')) {
        throw new InvalidArgumentException('Public and private storage must use different buckets.');
    }
    foreach (['AWS_ENDPOINT', 'AWS_PUBLIC_URL'] as $url) {
        if (parse_url(env($url), PHP_URL_SCHEME) !== 'https') {
            throw new InvalidArgumentException($url.' must use HTTPS.');
        }
    }
}

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => $mediaDriver === 's3' ? array_merge($remoteMedia, [
            'bucket' => env('AWS_PRIVATE_BUCKET'),
        ]) : [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => true,
            'report' => false,
        ],

        'public' => $mediaDriver === 's3' ? array_merge($remoteMedia, [
            'bucket' => env('AWS_PUBLIC_BUCKET'),
            'url' => env('AWS_PUBLIC_URL'),
        ]) : [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => true,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
