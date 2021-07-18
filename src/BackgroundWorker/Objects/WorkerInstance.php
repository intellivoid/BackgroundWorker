<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace BackgroundWorker\Objects;

    use ProcLib\Process;
    use TimerLib\Timer;

    /**
     * Class WorkerInstance
     * @package BackgroundWorker\Objects
     */
    class WorkerInstance
    {
        /**
         * The name of the worker instance
         *
         * @var string
         */
        public $Name;

        /**
         * The file path of the script that's being executed
         *
         * @var string
         */
        public $Path;

        /**
         * Displays the output of the process
         *
         * @var bool
         */
        public $DisplayOutput;

        /**
         * The ID of the instance (it doesn't change even if the process crashes)
         *
         * @var string
         */
        public $InstanceID;

        /**
         * The name of the monitoring function for this worker instance
         *
         * @var string
         */
        public $MonitoringFunctionName;

        /**
         * The process handle for the instance
         *
         * @var Process
         */
        public $Process;

        /**
         * The amount of times the process failed
         *
         * @var int
         */
        public $FailCount;

        /**
         * @var Timer
         */
        public $PingTimer;

        /**
         * The Unix Timestamp for when this worker started
         *
         * @var int
         */
        public $StartTimestamp;
    }