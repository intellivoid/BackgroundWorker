<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace BackgroundWorker;

    use BackgroundWorker\Exceptions\NoWorkersRunningException;
    use BackgroundWorker\Exceptions\UnexpectedTermination;
    use BackgroundWorker\Exceptions\WorkersAlreadyRunningException;
    use BackgroundWorker\Objects\WorkerInstance;
    use ProcLib\Abstracts\Types\StatusType;
    use ProcLib\Abstracts\Types\StdType;
    use ProcLib\Process;
    use ProcLib\Utilities\PhpExecutableFinder;
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
         * @var bool
         */
        private $displayOutput = false;

        /**
         * @var bool
         */
        private $stabilityCheck = true;

        /**
         * Supervisor constructor.
         * @param BackgroundWorker $backgroundWorker
         */
        public function __construct(BackgroundWorker $backgroundWorker)
        {
            $this->backgroundWorker = $backgroundWorker;
            self::$workerInstances = [];
        }

        /**
         * @return bool
         */
        public function isDisplayingOutput(): bool
        {
            return $this->displayOutput;
        }

        /**
         * @param string $name
         * @param bool $displayOutput
         * @throws WorkersAlreadyRunningException
         */
        public function setDisplayOutput(string $name, bool $displayOutput): void
        {
            // Check if workers are already running
            if(isset(self::$workerInstances[$name]) && count(self::$workerInstances[$name]) > 0)
            {
                /** @var WorkerInstance $instance */
                foreach(self::$workerInstances[$name] as $instance)
                {
                    if($instance->Process->isRunning())
                    {
                        throw new WorkersAlreadyRunningException("You cannot change the displayOutput property while workers are running");
                    }
                }
            }

            $this->displayOutput = $displayOutput;
        }

        /**
         * Initializes each worker and assigns a unique instance ID
         *
         * @param string $path
         * @param string $name
         * @param int $instances
         * @throws WorkersAlreadyRunningException
         * @throws UnexpectedTermination
         */
        public function startWorkers(string $path, string $name, int $instances=5)
        {
            // Check if workers are already running
            if(isset(self::$workerInstances[$name]) && count(self::$workerInstances[$name]) > 0)
            {
                /** @var WorkerInstance $instance */
                foreach(self::$workerInstances[$name] as $instance)
                {
                    if($instance->Process->isRunning())
                    {
                        throw new WorkersAlreadyRunningException("There are already workers running");
                    }
                }
            }

            // Find the PHP executable
            $phpExecutableFinder = new PhpExecutableFinder();
            $phpBinLocation = $phpExecutableFinder->find();

            // Begin spawning sub-processes
            $this->backgroundWorker->getLogHandler()->log(EventType::INFO, "Starting $instances instance(s)", get_class($this));
            self::$workerInstances[$name] = [];
            $current_time = (int)time();

            for ($i = 0; $i < $instances; $i++)
            {
                $instance = new WorkerInstance();
                $instance->Name = $name;
                $instance->Path = $path;
                $instance->InstanceID = hash('crc32', $current_time . $i);
                $instance->DisplayOutput = $this->displayOutput;
                $instance->Process = new Process([
                    $phpBinLocation, $path,
                    "--worker-instance=" . escapeshellarg($instance->InstanceID),
                    "--worker-name=" . escapeshellarg($instance->Name)]
                );

                $this->startProcess($instance);
                $this->backgroundWorker->getLogHandler()->log(
                    EventType::INFO, "Executed worker " . $instance->InstanceID . ", process ID " . $instance->Process->getPid(),
                    get_class($this)
                );

                self::$workerInstances[$name][] = $instance;
            }
        }

        /**
         * Verifies if the process has started successfully, if not throw an UnexpectedTermination error
         *
         * @param WorkerInstance $workerInstance
         * @return void
         * @throws UnexpectedTermination
         */
        private function checkStartup(WorkerInstance $workerInstance): void
        {
            while(true)
            {
                if($workerInstance->Process->getStatus() == StatusType::STATUS_TERMINATED)
                {
                    $exception = new UnexpectedTermination(
                        "The worker " . $workerInstance->InstanceID . " terminated unexpectedly",
                        $workerInstance->Process->getExitCode(),
                        $workerInstance->Process->getOutput(),
                        $workerInstance->Process->getErrorOutput(),
                    );

                    $this->backgroundWorker->getLogHandler()->logException($exception, $exception->getMessage(), get_class($this));
                    throw $exception;
                }

                if($workerInstance->Process->getStatus() == StatusType::STATUS_STARTED)
                {
                    return;
                }
            }

        }

        /**
         * Stops all workers by worker name
         *
         * @param string $name
         * @throws NoWorkersRunningException
         */
        public function stopWorkers(string $name)
        {
            if(isset(self::$workerInstances[$name]) == false || count(self::$workerInstances[$name]) == 0)
            {
                throw new NoWorkersRunningException("No workers are running for '$name'");
            }

            /** @var WorkerInstance $instance */
            foreach(self::$workerInstances[$name] as $instance)
            {
                if($instance->Process->isRunning())
                {
                    $this->backgroundWorker->getLogHandler()->log(EventType::INFO, "Killing instance " . $instance->InstanceID, get_class($this));
                    $instance->Process->stop();
                }
            }

            self::$workerInstances[$name] = [];
            $this->backgroundWorker->getLogHandler()->log(EventType::INFO, "Operation successful", get_class($this));
        }

        /**
         * Stops any existing workers and starts them up again
         *
         * @param string $name
         * @throws NoWorkersRunningException
         * @throws UnexpectedTermination
         */
        public function restartWorkers(string $name)
        {
            if(isset(self::$workerInstances[$name]) == false || count(self::$workerInstances[$name]) == 0)
            {
                throw new NoWorkersRunningException("No workers are running for '$name'");
            }

            /** @var WorkerInstance $instance */
            foreach(self::$workerInstances[$name] as $instance)
            {
                if($instance->Process->isRunning())
                {
                    $this->backgroundWorker->getLogHandler()->log(EventType::INFO, "Killing instance " . $instance->InstanceID, get_class($this));
                    $instance->Process->stop();
                }

                $this->backgroundWorker->getLogHandler()->log(EventType::INFO, "Restarting instance " . $instance->InstanceID, get_class($this));
                $this->startProcess($instance);
            }

            $this->backgroundWorker->getLogHandler()->log(EventType::INFO, "Operation successful", get_class($this));
        }

        /**
         * Starts the worker process
         *
         * @param WorkerInstance $instance
         * @throws UnexpectedTermination
         */
        private function startProcess(WorkerInstance &$instance)
        {
            $log_handler = $this->backgroundWorker->getLogHandler();

            $instance->Process->start(function ($type, $buffer) use ($instance, $log_handler) {
                if($instance->DisplayOutput == false)
                    return;

                $buffer_clean =  trim($buffer, " \n\r\t\v\0");

                switch(strtolower($type))
                {
                    case "out":
                    case StdType::STDOUT:
                        $log_handler->log(EventType::INFO, $buffer_clean, "instance-" . $instance->InstanceID);
                        break;

                    case "err":
                    case StdType::STDERR:
                        $log_handler->log(EventType::ERROR, $buffer_clean, "instance-" . $instance->InstanceID);
                        break;
                }
            });

            $this->checkStartup($instance);
        }

        /**
         * Monitors the instances and restarts them when necessary and preforms stability checks if enabled
         *
         * @param string $name
         * @throws NoWorkersRunningException
         * @throws UnexpectedTermination
         */
        public function monitor(string $name): void
        {
            if(isset(self::$workerInstances[$name]) == false || count(self::$workerInstances[$name]) == 0)
            {
                throw new NoWorkersRunningException("No workers are running for '$name'");
            }

            /** @var WorkerInstance $instance */
            foreach(self::$workerInstances[$name] as $instance)
            {
                if($instance->Process->isRunning() == false)
                {
                    if($instance->FailCount >= 5 && $this->stabilityCheck)
                    {
                        $exception = new UnexpectedTermination(
                            "The worker " . $instance->InstanceID . " terminated unexpectedly repeatedly (StabilityCheck Failed)",
                            $instance->Process->getExitCode(),
                            $instance->Process->getOutput(),
                            $instance->Process->getErrorOutput(),
                        );

                        $this->backgroundWorker->getLogHandler()->logException($exception, $exception->getMessage(), get_class($this));
                        throw $exception;
                    }

                    $this->backgroundWorker->getLogHandler()->log(EventType::WARNING, "Worker " . $instance->InstanceID . " has terminated unexpectedly, restarting.", get_class($this));
                    $instance->FailCount += 1;
                    $this->startProcess($instance);
                };
            }
        }

        /**
         * Determines if StabilityCheck is enabled
         *
         * @return bool
         */
        public function isStabilityCheck(): bool
        {
            return $this->stabilityCheck;
        }

        /**
         * Sets the StabilityCheck, this will cause the supervisor to halt operations if one or more processes seems to
         * be misbehaving (eg; repetitive unexpected terminations)
         *
         * @param bool $stabilityCheck
         */
        public function setStabilityCheck(bool $stabilityCheck): void
        {
            $this->stabilityCheck = $stabilityCheck;
        }
    }