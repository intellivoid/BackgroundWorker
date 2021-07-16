<?php

    require("ppm");
    ppm_import("net.intellivoid.background_worker");

    $BackgroundWorker = new \BackgroundWorker\BackgroundWorker();

    $BackgroundWorker->getClient()->addServer();
    $BackgroundWorker->getSupervisor()->startWorkers(
        __DIR__ . DIRECTORY_SEPARATOR . 'worker.php', "example_worker", 10
    );

    sleep(5);
    $BackgroundWorker->getSupervisor()->stopWorkers("example_worker");