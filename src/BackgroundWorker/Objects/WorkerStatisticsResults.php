<?php


    namespace BackgroundWorker\Objects;

    use BackgroundWorker\Abstracts\WorkerState;
    use TimerLib\Objects\Duration;

    /**
     * Class WorkerStatisticsResults
     * @package BackgroundWorker\Objects
     */
    class WorkerStatisticsResults
    {
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
         * The memory usage of the worker
         *
         * @var int
         */
        public $MemoryUsage;

        /**
         * The real memory usage of the worker
         *
         * @var int
         */
        public $RealMemoryUsage;

        /**
         * The worker uptime in seconds
         *
         * @var int
         */
        public $Uptime;

        /**
         * @var Duration
         */
        public $PingResponseDuration;

        /**
         * @var Duration
         */
        public $PingAvgResponseDuration;

        /**
         * WorkerStatisticsResults constructor.
         * @param WorkerInstance $workerInstance
         */
        public function __construct(WorkerInstance $workerInstance)
        {
            $this->InstanceID = $workerInstance->InstanceID;
            $this->MonitoringFunctionName = $workerInstance->MonitoringFunctionName;
        }
    }