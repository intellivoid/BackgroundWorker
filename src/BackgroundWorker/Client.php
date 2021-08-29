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
         * Client constructor.
         */
        public function __construct()
        {
            $this->GearmanClient = null;
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
            try
            {
                $this->getGearmanClient()->addServer($host, $port);
            }
            catch(Exception $e)
            {
                throw new ServerNotReachableException('Cannot add server ' . $host . ':' . $port . ', server unreachable [Client.addServer() error]');
            }
        }

        /**
         * @return GearmanClient
         */
        public function getGearmanClient(): GearmanClient
        {
            if($this->GearmanClient == null)
            {
                $this->GearmanClient = new GearmanClient();
            }

            return $this->GearmanClient;
        }
    }