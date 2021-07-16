<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace BackgroundWorker\Objects;

    use ProcLib\Process;

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
    }