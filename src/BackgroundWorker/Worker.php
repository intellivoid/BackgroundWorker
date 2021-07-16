<?php


    namespace BackgroundWorker;


    use BackgroundWorker\Exceptions\WorkerException;
    use GearmanWorker;

    /**
     * Class Worker
     * @package BackgroundWorker
     */
    class Worker
    {
        /**
         * @var GearmanWorker
         */
        private $GearmanWorker;

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
            $this->getGearmanWorker()->addServer($host, $port);
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
                /** @noinspection PhpStatementHasEmptyBodyInspection */
                while($this->getGearmanWorker()->work());
            }
            else
            {
                $this->getGearmanWorker()->setTimeout($timeout);

                while(@$this->getGearmanWorker()->work() || $this->getGearmanWorker()->returnCode() == GEARMAN_TIMEOUT)
                {
                    if ($this->getGearmanWorker()->returnCode() == GEARMAN_TIMEOUT)
                        break;

                    if ($this->getGearmanWorker()->returnCode() != GEARMAN_SUCCESS)
                    {
                        if($throw_errors)
                            throw new WorkerException("Gearman returned error code " . $this->getGearmanWorker()->returnCode());
                        break;
                    }
                }

            }
        }


    }