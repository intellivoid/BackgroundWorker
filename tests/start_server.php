<?php

    $Source = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    include_once($Source . 'BackgroundWorker' . DIRECTORY_SEPARATOR . 'BackgroundWorker.php');

    $BackgroundWorker = new \BackgroundWorker\BackgroundWorker();

    $BackgroundWorker->getClient()->addServer();
    $BackgroundWorker->getSupervisor()->startWorkers(
        __DIR__ . DIRECTORY_SEPARATOR . 'worker.php', "example_worker", 10
    );