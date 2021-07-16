<?php

    require("ppm");
    ppm_import("net.intellivoid.background_worker");

    $BackgroundWorker = new \BackgroundWorker\BackgroundWorker();
    \VerboseAdventure\VerboseAdventure::setStdout(true);

    $BackgroundWorker->getClient()->addServer();
    $BackgroundWorker->getSupervisor()->startWorkers(
        __DIR__ . DIRECTORY_SEPARATOR . 'worker.php', "example_worker", 10
    );

    while(true)
    {
        $BackgroundWorker->getSupervisor()->monitor("example_worker"); // Make sure the instances are working correctly
        sleep(1); // Reduce CPU usage
    }