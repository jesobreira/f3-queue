<?php
require('vendor/autoload.php');
$f3=Base::instance();

$f3->mset(array(
    'AUTOLOAD'=>'tests/',
    'UI'=>'tests/',
    'TEMP'=>'var/tmp/',
    'LOGS'=>'var/log/',
    'DEBUG'=>3,
));

$f3->route('GET /','Tests->run');

$queue = Queue::instance();

$cron->set('test1','Jobs->test1');

$f3->run();
