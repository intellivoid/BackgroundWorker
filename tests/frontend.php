<?php


    require("ppm");
    ppm_import("net.intellivoid.background_worker");

    $BackgroundWorker = new \BackgroundWorker\BackgroundWorker();

    $BackgroundWorker->getClient()->addServer();
    $BackgroundWorker->getClient()->getGearmanClient()->doBackground("sleep", json_encode(array("seconds"=>"5")));