<?php


    namespace BackgroundWorker;

    use VerboseAdventure\Abstracts\EventType;

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
         * @var array
         */
        private static $workerInstances;

        /**
         * Supervisor constructor.
         * @param BackgroundWorker $backgroundWorker
         */
        public function __construct(BackgroundWorker $backgroundWorker)
        {
            $this->backgroundWorker = $backgroundWorker;
        }

        /**
         * Initializes each worker and assigns a unique instance ID
         *
         * @param string $path
         * @param string $name
         * @param int $instances
         */
        public function startWorkers(string $path, string $name, int $instances=5)
        {
            $this->backgroundWorker->getLogHandler()->log(EventType::INFO, "Starting $instances instance(s)", get_class($this));
            self::$workerInstances = [];
            $current_time = (int)time();
            for ($i = 0; $i < $instances; $i++)
            {
                $instance_id = hash('crc32', $current_time . $i);
                exec("php " . escapeshellarg($path) . " --worker-instance=" . escapeshellarg($instance_id) . " --worker-name=" . escapeshellarg($name) . " > /dev/null 2>/dev/null &");
                $this->backgroundWorker->getLogHandler()->log(EventType::INFO, "Executed worker " . $instance_id, get_class($this));
                self::$workerInstances = $instance_id;
            }
        }

        /**
         * Stops all workers by worker name
         *
         * @param string $name
         */
        public function stopWorkers(string $name)
        {
            $this->backgroundWorker->getLogHandler()->log(EventType::INFO, "Killing all workers by $name", get_class($this));
            exec("pkill -f " . escapeshellarg("worker-name=" . $name));
            $this->backgroundWorker->getLogHandler()->log(EventType::INFO, "Operation successful", get_class($this));
        }

        /**
         * Stops any existing workers and starts them up again
         *
         * @param string $path
         * @param string $name
         * @param int $instances
         */
        public function restartWorkers(string $path, string $name, int $instances=5)
        {
            $this->backgroundWorker->getLogHandler()->log(EventType::INFO, "Restarting workers", get_class($this));
            $this->stopWorkers($name);
            $this->startWorkers($path, $name, $instances);
        }
    }