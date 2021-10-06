<?php


    namespace BackgroundWorker;


    use BackgroundWorker\Exceptions\ServerNotReachableException;
    use BackgroundWorker\Objects\WorkerInstance;
    use BackgroundWorker\Objects\WorkerStatisticsResults;
    use Exception;
    use GearmanClient;

    /**
     * Class Client
     * @package BackgroundWorker
     */
    class Client
    {
        /**
         * @var GearmanClient
         */
        private $GearmanClient;

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
         * Client constructor.
         */
        public function __construct()
        {
            $this->GearmanClient = null;
        }

        /**
         * Reconnects to the server socket
         */
        public function reconnect()
        {
            unset($this->GearmanClient);
            $this->GearmanClient = null;

            $this->GearmanClient = new GearmanClient();

            foreach($this->Servers as $host => $port)
            {
                try
                {
                    $this->getGearmanClient()->addServer($host, $port);
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
         * Adds a job server to a list of servers that can be used to run a task.
         *
         * @param string $host
         * @param int $port
         * @throws ServerNotReachableException
         */
        public function addServer(string $host="127.0.0.1", int $port=4730)
        {
            $this->Servers[$host] = $port;

            try
            {
                $this->getGearmanClient()->addServer($host, $port);
            }
            catch(Exception $e)
            {
                throw new ServerNotReachableException('Cannot add server ' . $host . ':' . $port . ', server unreachable [Client.addServer() error]', null, $e);
            }
        }

        /**
         * @return GearmanClient
         */
        public function getGearmanClient(): GearmanClient
        {
            $this->checkAutoRestart();

            if($this->GearmanClient == null)
            {
                $this->GearmanClient = new GearmanClient();
            }

            return $this->GearmanClient;
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

        /**
         * @return int|null
         */
        public function getNextRestart(): ?int
        {
            return $this->NextRestart;
        }
    }