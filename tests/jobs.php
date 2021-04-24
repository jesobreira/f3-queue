<?php

class Jobs {

    function run($f3, $params) {
        echo 'Hello, ' . $params->name;
    }

    function run2($f3, $params) {
        echo 'Hi, ' . $params->name;
    }

}
