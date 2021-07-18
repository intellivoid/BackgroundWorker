<?php

    require("ppm");
    ppm_import("net.intellivoid.background_worker");

    $BackgroundWorker = new \BackgroundWorker\BackgroundWorker();
    \VerboseAdventure\VerboseAdventure::setStdout(true);

    $BackgroundWorker->getSupervisor()->addServer();
    $BackgroundWorker->getSupervisor()->setDisplayOutput("example_worker", false); // Display output
    $BackgroundWorker->getSupervisor()->startWorkers(
        __DIR__ . DIRECTORY_SEPARATOR . 'worker.php', "example_worker", 10
    );

    $BackgroundWorker->getSupervisor()->monitor_loop("example_worker");