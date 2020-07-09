<?php

    $Source = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    include_once($Source . 'BackgroundWorker' . DIRECTORY_SEPARATOR . 'BackgroundWorker.php');

    // Initialize Background Worker
    $BackgroundWorker = new \BackgroundWorker\BackgroundWorker();

    // Start 10 instances of worker.php
    //
    // This will automatically kill the old instances if you run this script more than once.
    // So you don't accidentally start 20 workers for example
    $BackgroundWorker->getClient()->addServer();
    $BackgroundWorker->getSupervisor()->startWorkers(
        __DIR__ . DIRECTORY_SEPARATOR . 'worker.php', "example_worker", 10
    );