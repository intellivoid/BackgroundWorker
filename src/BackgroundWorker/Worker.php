<?php /** @noinspection PhpUnusedPrivateFieldInspection */

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
         * @var array
         */
        private $RegisteredFunctions = [];

        /**
         * @var null|int
         */
        private $Timeout = -1;

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
            catch(Exception $e)
            {
                exit(15);
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

            $this->checkAutoRestart();
            return $this->GearmanWorker;
        }

        /**
         * @return string|null
         * @noinspection PhpUnused
         */
        public function getWorkerInstanceID(): ?string
        {
            return $this->WorkerInstanceID;
        }

        /**
         * @return string|null
         * @noinspection PhpUnused
         */
        public function getWorkerName(): ?string
        {
            return $this->WorkerName;
        }

        /**
         * @return int|null
         * @noinspection PhpUnused
         */
        public function getNextRestart(): ?int
        {
            return $this->NextRestart;
        }

        /**
         * Self identifies the worker for monitoring purposes
         *
         * @return bool
         * @noinspection PhpUnusedPrivateMethodInspection
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
         * @param $function_name
         * @param $function
         * @param null $context
         * @param int $timeout
         * @noinspection PhpMissingParamTypeInspection
         */
        public function addFunction($function_name, $function, $context = null, $timeout = 0)
        {
            $this->GearmanWorker->addFunction($function_name, $function, $context, $timeout);
            $this->RegisteredFunctions[$function_name] = [
                $function, $context, $timeout
            ];
        }

        /**
         * Sets the interval of time to wait for socket I/O activity.
         *
         * @param int $timeout
         */
        public function setTimeout(int $timeout)
        {
            $this->GearmanWorker->setTimeout($timeout);
            $this->Timeout = $timeout;
        }

        /**
         * Reconnects to the server socket
         */
        public function reconnect()
        {
            unset($this->GearmanWorker);
            $this->GearmanWorker = null;
            $this->GearmanWorker = new GearmanWorker();

            // Re-add servers
            foreach($this->Servers as $host => $port)
            {
                try
                {
                    $this->GearmanWorker->addServer($host, $port);
                }
                catch(Exception)
                {
                    exit(15);
                }
            }

            // Re-add functions
            foreach($this->RegisteredFunctions as $function_name => $properties)
            {
                $this->GearmanWorker->addFunction($function_name,
                    $properties[0], $properties[1], $properties[2]
                );
            }

            $this->setTimeout($this->Timeout);
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
                    $this->NextRestart = time() + rand(1800, 3600);
                }

                if(time() >= $this->NextRestart)
                {
                    $this->NextRestart = time() + rand(1800, 3600);
                    $this->reconnect();
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
            $this->setTimeout($timeout);

            while(true)
            {
                $this->checkAutoRestart();
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
                if ($this->getGearmanWorker()->returnCode() == GEARMAN_TIMEOUT && $blocking == false)
                    break;
                if ($this->getGearmanWorker()->returnCode() != GEARMAN_SUCCESS && $throw_errors)
                    throw new WorkerException("Gearman returned error code " . $this->getGearmanWorker()->returnCode());
            }
        }

        /**
         * @return bool
         * @noinspection PhpUnused
         */
        public function isIgnoreConnectionError(): bool
        {
            return $this->IgnoreConnectionError;
        }

        /**
         * @param bool $IgnoreConnectionError
         * @noinspection PhpUnused
         */
        public function setIgnoreConnectionError(bool $IgnoreConnectionError): void
        {
            $this->IgnoreConnectionError = $IgnoreConnectionError;
        }

        /**
         * @return bool
         * @noinspection PhpUnused
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