<?php

    require("ppm");
    ppm_import("net.intellivoid.background_worker");

    $BackgroundWorker = new \BackgroundWorker\BackgroundWorker();

    $BackgroundWorker->getWorker()->addServer();
    $BackgroundWorker->getWorker()->setAutoRestart(true);

    $BackgroundWorker->getWorker()->addFunction("sleep", function(GearmanJob $job){
        print("Processing job" . PHP_EOL);
        $workload = json_decode($job->workload(), true);
        sleep($workload["seconds"]);
        file_get_contents("https://api.telegram.org/bot865804194:AAEpVLQjthh-RuILNkXdFDqEYtPoSXHtTh8/sendmessage?chat_id=570787098&text=200%20OK");
        print("Completed" . PHP_EOL);
    });

    print("Worker started " . PHP_EOL);
    $BackgroundWorker->getWorker()->work();