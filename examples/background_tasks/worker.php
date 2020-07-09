<?php

    $Source = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    include_once($Source . 'BackgroundWorker' . DIRECTORY_SEPARATOR . 'BackgroundWorker.php');

    // Initialize BackgroundWorker
    $BackgroundWorker = new \BackgroundWorker\BackgroundWorker();
    $BackgroundWorker->getWorker()->addServer();

    // This is an outside variable, eg; database.
    $outside_variable = "Foo Bar!";

    // Register the function send_email
    $BackgroundWorker->getWorker()->getGearmanWorker()->addFunction("send_email", function(GearmanJob $job) use ($outside_variable){
        print("Processing job" . PHP_EOL);
        print($outside_variable . PHP_EOL);
        $workload = json_decode($job->workload(), true);
        sleep($workload["seconds"]);
        print("Completed" . PHP_EOL);
    });

    // Work! (Blocking method)
    $BackgroundWorker->getWorker()->work();