<?php


    namespace BackgroundWorker;

    use VerboseAdventure\Abstracts\EventType;
    use VerboseAdventure\Classes\ErrorHandler;
    use VerboseAdventure\VerboseAdventure;

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
            ErrorHandler::registerHandlers();
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