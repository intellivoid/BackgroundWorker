<?php

    $Source = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    include_once($Source . 'BackgroundWorker' . DIRECTORY_SEPARATOR . 'BackgroundWorker.php');

    $BackgroundWorker = new \BackgroundWorker\BackgroundWorker();

    $BackgroundWorker->getWorker()->addServer();

    $BackgroundWorker->getWorker()->getGearmanWorker()->addFunction("sleep", function(GearmanJob $job){
        print("Processing job" . PHP_EOL);
        $workload = json_decode($job->workload(), true);
        sleep($workload["seconds"]);
        print("Completed" . PHP_EOL);
    });

    $BackgroundWorker->getWorker()->work();