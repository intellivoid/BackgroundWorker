<?php


    namespace BackgroundWorker;

    /**
     * Class Supervisor
     * @package BackgroundWorker
     */
    class Supervisor
    {
        /**
         * @var BackgroundWorker
         */
        private $backgroundWorker;

        /**
         * Supervisor constructor.
         * @param BackgroundWorker $backgroundWorker
         */
        public function __construct(BackgroundWorker $backgroundWorker)
        {
            $this->backgroundWorker = $backgroundWorker;
        }
    }