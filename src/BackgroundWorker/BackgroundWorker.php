<?php


    namespace BackgroundWorker;

    use VerboseAdventure\Abstracts\EventType;
    use VerboseAdventure\Classes\ErrorHandler;
    use VerboseAdventure\VerboseAdventure;

    if(defined("PPM") == false)
    {
        include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Client.php');
        include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Supervisor.php');
        include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Worker.php');
    }

    ErrorHandler::registerHandlers();

    /**
     * Class BackgroundWorker
     * @package BackgroundWorker
     */
    class BackgroundWorker
    {
        /**
         * @var Worker
         */
        private $Worker;

        /**
         * @var Client
         */
        private $Client;

        /**
         * @var Supervisor
         */
        private $Supervisor;

        /**
         * @var VerboseAdventure
         */
        private VerboseAdventure $LogHandler;

        /**
         * BackgroundWorker constructor.
         */
        public function __construct()
        {
            $this->Worker = new Worker();
            $this->Client = new Client();
            $this->Supervisor = new Supervisor($this);
            $this->LogHandler = new VerboseAdventure("BackgroundWorker");

            $this->LogHandler->log(EventType::INFO, "Initialized");
        }

        /**
         * @return Worker
         */
        public function getWorker(): Worker
        {
            return $this->Worker;
        }

        /**
         * @return Client
         */
        public function getClient(): Client
        {
            return $this->Client;
        }

        /**
         * @return Supervisor
         */
        public function getSupervisor(): Supervisor
        {
            return $this->Supervisor;
        }

        /**
         * @return VerboseAdventure
         */
        public function getLogHandler(): VerboseAdventure
        {
            return $this->LogHandler;
        }
    }