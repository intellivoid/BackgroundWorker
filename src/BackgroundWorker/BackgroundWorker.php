<?php


    namespace BackgroundWorker;

    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Client.php');
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'Worker.php');

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
         * BackgroundWorker constructor.
         */
        public function __construct()
        {
            $this->Worker = new Worker();
            $this->Client = new Client();
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

    }