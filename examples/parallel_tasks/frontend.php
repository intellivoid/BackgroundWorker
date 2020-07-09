<?php


    $Source = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    include_once($Source . 'BackgroundWorker' . DIRECTORY_SEPARATOR . 'BackgroundWorker.php');

    // Initialize Background Worker
    $BackgroundWorker = new \BackgroundWorker\BackgroundWorker();

    // Execute the function 'send_email'
    $BackgroundWorker->getClient()->addServer();

    $send_email_results = null;
    $spam_email_results = null;

    // This sets up what gearman will callback to as tasks are returned to us.
    // The $context helps us know which function is being returned so we can
    // handle it correctly.
    $BackgroundWorker->getClient()->getGearmanClient()->setCompleteCallback(
        function(GearmanTask $task, $context) use (&$send_email_results, &$spam_email_results){
            switch($context) {
                case 'send_email_context':
                    $send_email_results = $task->data();
                    break;
                case 'spam_email_context':
                    $spam_email_results = $task->data();
                    break;
            }
        });


    // Setup the tasks
    $BackgroundWorker->getClient()->getGearmanClient()->addTask(
        'send_email', json_encode(array("seconds"=>"5")), 'send_email_context');

    $BackgroundWorker->getClient()->getGearmanClient()->addTask(
        'spam_email', json_encode(array("seconds"=>"5")), 'spam_email_context');

    print("Running two tasks that takes 5 seconds each" . PHP_EOL);
    $BackgroundWorker->getClient()->getGearmanClient()->runTasks();

    print("Done" . PHP_EOL);
    var_dump($send_email_results);
    var_dump($spam_email_results);

    exit();