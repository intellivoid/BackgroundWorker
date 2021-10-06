<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace BackgroundWorker;

    use BackgroundWorker\Exceptions\WorkerException;
    use Exception;
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
         * @var bool
         */
        private bool $monitoringFunctionRegistered = false;

        /**
         * @var bool
         */
        private $IgnoreConnectionError = false;

        /**
         * @var bool
         */
        private $AutoRestart = false;

        /**
         * @var null|int
         */
        private $NextRestart = null;

        /**
         * @var string[]
         */
        private $Servers = [];

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
            $this->Servers[$host] = $port;

            try
            {
                $this->getGearmanWorker()->addServer($host, $port);
            }
            catch(Exception)
            {
                exit(15);
            }

            /**
            if($this->monitoringFunctionRegistered == false && $this->identifyWorker() == true)
            {
                // Register the internal monitoring function
                $this->getGearmanWorker()->addFunction(Converter::calculateWorkerInternalId($this->WorkerName, $this->WorkerInstanceID), function(GearmanJob $job){
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
            **/
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
         * Reconnects to the server socket
         */
        public function reconnect()
        {
            unset($this->GearmanWorker);
            $this->GearmanWorker = null;

            $this->GearmanWorker = new GearmanWorker();

            foreach($this->Servers as $host => $port)
            {
                try
                {
                    $this->getGearmanWorker()->addServer($host, $port);
                }
                catch(Exception)
                {
                    exit(15);
                }
            }
        }

        /**
         * Checks if the socket needs to be restarted
         */
        public function checkAutoRestart(): void
        {
            // Disconnect and reconnect
            if($this->AutoRestart == true)
            {
                if($this->NextRestart == null)
                {
                    $this->NextRestart = time() + rand(3600, 7200);
                }

                if(time() >= $this->NextRestart)
                {
                    $this->reconnect();
                    $this->NextRestart = time() + rand(3600, 7200);
                }
            }
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
            $this->checkAutoRestart();
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

        /**
         * @return bool
         */
        public function isAutoRestart(): bool
        {
            return $this->AutoRestart;
        }

        /**
         * @param bool $AutoRestart
         */
        public function setAutoRestart(bool $AutoRestart): void
        {
            $this->AutoRestart = $AutoRestart;
        }


    }