<?php


    $Source = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    include_once($Source . 'BackgroundWorker' . DIRECTORY_SEPARATOR . 'BackgroundWorker.php');

    // Initialize Background Worker
    $BackgroundWorker = new \BackgroundWorker\BackgroundWorker();

    // Execute the function 'send_email'
    $BackgroundWorker->getClient()->addServer();
    $BackgroundWorker->getClient()->getGearmanClient()->doBackground("send_email", json_encode(array("seconds"=>"5")));

    // This should show even if the above function is processing sleep(5)
    print("This will show even if the worker is sleeping for 5 seconds." . PHP_EOL);
    exit();