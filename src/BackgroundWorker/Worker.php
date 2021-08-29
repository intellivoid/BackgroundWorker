<?php


    namespace BackgroundWorker;


    use BackgroundWorker\Abstracts\WorkerMonitorCommands;
    use BackgroundWorker\Exceptions\ServerNotReachableException;
    use BackgroundWorker\Exceptions\WorkerException;
    use BackgroundWorker\Utilities\Converter;
    use Exception;
    use GearmanJob;
    use GearmanWorker;

    /**
     * Class Worker
     * @package BackgroundWorker
     */
    class Worker
    {
        /**
         * @var GearmanWorker|null
         */
        private ?GearmanWorker $GearmanWorker;

        /**
         * @var string|null
         */
        private $WorkerInstanceID;

        /**
         * @var string|null
         */
        private $WorkerName;

        /**
         * @var string|null
         */
        private $WorkerMon;

        /**
         * The Unix Timestamp when this worker started
         *
         * @var int
         */
        private $TimestampStart;

        /**
         * @var bool
         */
        private bool $monitoringFunctionRegistered = false;

        /**
         * @var bool
         */
        private $IgnoreConnectionError = false;

        /**
         * Worker constructor.
         */
        public function __construct()
        {
            $this->GearmanWorker = null;
        }

        /**
         * Adds one or more job servers to this worker. These go into a list of servers
         * that can be used to run jobs. No socket I/O happens here.
         *
         * @param string $host
         * @param int $port
         */
        public function addServer(string $host="127.0.0.1", int $port=4730)
        {
            try
            {
                $this->getGearmanWorker()->addServer($host, $port);
            }
            catch(Exception)
            {
                exit(15);
            }

            if($this->monitoringFunctionRegistered == false && $this->identifyWorker() == true)
            {
                // Register the internal monitoring function
                $this->getGearmanWorker()->addFunction(Converter::calculateWorkerInternalId($this->WorkerName, $this->WorkerInstanceID), function(GearmanJob $job){
                    /** @noinspection PhpSwitchCanBeReplacedWithMatchExpressionInspection */
                    switch($job->workload())
                    {
                        case WorkerMonitorCommands::PING:
                            return true;

                        case WorkerMonitorCommands::GET_MEMORY_USAGE:
                            return memory_get_usage();

                        case WorkerMonitorCommands::GET_REAL_MEMORY_USAGE:
                            return memory_get_usage(true);

                        case WorkerMonitorCommands::GET_UPTIME:
                            return time() - $this->TimestampStart;

                        default:
                            return null;
                    }
                });

                $this->monitoringFunctionRegistered = true;
                $this->TimestampStart = time();
                $this->getGearmanWorker()->setId($this->getGearmanWorker()->setId($this->getWorkerInstanceID()));
            }
            else
            {
                trigger_error("This worker process was not executed by a supervisor, monitoring features will not be available.", E_USER_WARNING);
            }
        }


        /**
         * @return GearmanWorker
         */
        public function getGearmanWorker(): GearmanWorker
        {
            if($this->GearmanWorker == null)
            {
                $this->GearmanWorker = new GearmanWorker();
            }

            return $this->GearmanWorker;
        }

        /**
         * @return string|null
         */
        public function getWorkerInstanceID(): ?string
        {
            return $this->WorkerInstanceID;
        }

        /**
         * @return string|null
         */
        public function getWorkerName(): ?string
        {
            return $this->WorkerName;
        }

        /**
         * Self identifies the worker for monitoring purposes
         *
         * @return bool
         */
        private function identifyWorker(): bool
        {
            $long_opts = ["worker-instance::", "worker-name::", "worker-mon::"];
            $args = getopt("", $long_opts);

            if(isset($args["worker-instance"]) && strlen($args["worker-instance"]) > 0)
            {
                $this->WorkerInstanceID = $args["worker-instance"];
            }
            else
            {
                return false;
            }

            if(isset($args["worker-name"]) && strlen($args["worker-name"]) > 0)
            {
                $this->WorkerName = $args["worker-name"];
            }
            else
            {
                return false;
            }

            return true;
        }

        /**
         * Works and listens for incoming jobs
         *
         * @param bool $blocking If false, the method will stop executing indicated by the timeout
         * @param int $timeout The time for this method to execute (in milliseconds)
         * @param bool $throw_errors Throws errors if any errors are caught while in non-blocking mode
         * @throws WorkerException
         */
        public function work(bool $blocking=true, int $timeout=500, bool $throw_errors=false)
        {
            if($blocking)
            {
                while(true)
                {
                    @$this->getGearmanWorker()->work();

                    if($this->getGearmanWorker()->returnCode() == GEARMAN_COULD_NOT_CONNECT)
                    {
                        if($this->IgnoreConnectionError == false)
                        {
                            sleep(10);
                        }
                        else
                        {
                            exit(15);
                        }
                    }
                }
            }
            else
            {
                $this->getGearmanWorker()->setTimeout($timeout);

                while(true)
                {
                    @$this->getGearmanWorker()->work();
                    if($this->getGearmanWorker()->returnCode() == GEARMAN_COULD_NOT_CONNECT )
                    {
                        if($this->IgnoreConnectionError == false)
                        {
                            sleep(10);
                        }
                        else
                        {
                            exit(15);
                        }
                    }
                    if($this->getGearmanWorker()->returnCode() == GEARMAN_TIMEOUT)
                        continue;
                    if ($this->getGearmanWorker()->returnCode() == GEARMAN_TIMEOUT)
                        break;
                    if ($this->getGearmanWorker()->returnCode() != GEARMAN_SUCCESS && $throw_errors)
                        throw new WorkerException("Gearman returned error code " . $this->getGearmanWorker()->returnCode());
                }

            }
        }

        /**
         * @return bool
         */
        public function isIgnoreConnectionError(): bool
        {
            return $this->IgnoreConnectionError;
        }

        /**
         * @param bool $IgnoreConnectionError
         */
        public function setIgnoreConnectionError(bool $IgnoreConnectionError): void
        {
            $this->IgnoreConnectionError = $IgnoreConnectionError;
        }


    }