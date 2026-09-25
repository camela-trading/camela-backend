<?php
$target = '/home/u181332690/domains/api.camela.com.sg/laravel/storage/app/public';
$shortcut = __DIR__ . '/storage';

if (symlink($target, $shortcut)) {
    echo "Symlink created successfully!";
} else {
    echo "Failed to create symlink.";
}