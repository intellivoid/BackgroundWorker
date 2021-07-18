<?php


    namespace BackgroundWorker\Abstracts;

    /**
     * Class WorkerMonitorCommands
     * @package BackgroundWorker\Abstracts
     */
    abstract class WorkerMonitorCommands
    {
        const PING = 0;

        const GET_MEMORY_USAGE = 1;

        const GET_REAL_MEMORY_USAGE = 2;

        const GET_UPTIME = 3;
    }