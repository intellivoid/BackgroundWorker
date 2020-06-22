<?php

    $Source = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    include_once($Source . 'BackgroundWorker' . DIRECTORY_SEPARATOR . 'BackgroundWorker.php');

    $BackgroundWorker = new \BackgroundWorker\BackgroundWorker();

    $BackgroundWorker->getWorker()->addServer();

    $BackgroundWorker->getWorker()->getGearmanWorker()->addFunction("sleep", function(GearmanJob $job){
        print("Processing job" . PHP_EOL);
        $workload = json_decode($job->workload(), true);
        sleep($workload["seconds"]);
        file_get_contents("https://api.telegram.org/bot869979136:AAEi_uxDobRLwhC0wF0TMfkqAoy8IC0fA-0/sendmessage?chat_id=570787098&text=200%20OK");
        print("Completed" . PHP_EOL);
    });

    $BackgroundWorker->getWorker()->work();