<?php


    $Source = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    include_once($Source . 'BackgroundWorker' . DIRECTORY_SEPARATOR . 'BackgroundWorker.php');

    $BackgroundWorker = new \BackgroundWorker\BackgroundWorker();

    $BackgroundWorker->getClient()->addServer();
    $BackgroundWorker->getClient()->getGearmanClient()->doBackground("sleep", json_encode(array("seconds"=>"5")));