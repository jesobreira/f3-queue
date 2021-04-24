<?php

class Tests {

    function run($f3) {
        $queue = Queue::instance();

        $job_id = $queue->dispatch('test1', [
            'name' => 'world'
        ]);

        echo 'Job dispatched! ID: ' . $job_id;
    }

}
