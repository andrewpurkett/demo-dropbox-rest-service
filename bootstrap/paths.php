<?php

if ($env == 'local') {
    return [
        'app' => __DIR__.'/../app',
        'public' => __DIR__.'/../public',
        'base' => __DIR__.'/..',
        'storage' => '/storage/dropbox',
    ];
}
elseif ($env == 'production') {
    return [
        'app' => __DIR__.'/../app',
        'public' => __DIR__.'/../public',
        'base' => __DIR__.'/..',
        'storage' => '/tmp/storage',
    ];
}
elseif ($env == 'staging') {
    return [
        'app' => __DIR__.'/../app',
        'public' => __DIR__.'/../public',
        'base' => __DIR__.'/..',
        'storage' => '/tmp/storage',
    ];
}
else {
    trigger_error("Could not find path definitions for environment {$env}");
}

