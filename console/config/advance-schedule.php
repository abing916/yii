<?php
$config = [
    [
        'id' => 'test_one',
        'title' => '一秒一次',
        'cron' => '*/1 * * * * *',
        'command' => 'test/pre-one-second',
    ],
    [
        'id' => 'test_ten',
        'title' => '10秒一次',
        'cron' => '*/10 * * * * *',
        'command' => 'test/pre-ten-second',
    ]
];

return $config;
