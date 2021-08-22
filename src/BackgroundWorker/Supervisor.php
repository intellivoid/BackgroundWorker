<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace BackgroundWorker;

    use BackgroundWorker\Abstracts\WorkerMonitorCommands;
    use BackgroundWorker\Exceptions\NoWorkersRunningException;
    use BackgroundWorker\Exceptions\UnexpectedTermination;
    use BackgroundWorker\Exceptions\WorkerException;
    use BackgroundWorker\Exceptions\WorkersAlreadyRunningException;
    use BackgroundWorker\Objects\WorkerInstance;
    use BackgroundWorker\Objects\WorkerStatisticsResults;
    use BackgroundWorker\Utilities\Converter;
    use GearmanException;
    use ProcLib\Abstracts\Types\StatusType;
    use ProcLib\Abstracts\Types\StdType;
    use ProcLib\Process;
    use ProcLib\Utilities\PhpExecutableFinder;
    use TimerLib\Exceptions\DurationNotAvailableException;
    use TimerLib\Objects\Duration;
    use TimerLib\Timer;
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
         * @var array
         */
        private $monitoringTimestamps;

        /**
         * @var array
         */
        private $resourceWarningTimestamps;

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
         * @param string $host
         * @param int $port
         */
        public function addServer(string $host="127.0.0.1", int $port=4730)
        {
            $this->backgroundWorker->getClient()->addServer($host, $port);
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
                $instance->MonitoringFunctionName = Converter::calculateWorkerInternalId($name, $instance->InstanceID);
                $instance->Process = new Process([
                    $phpBinLocation, $path,
                    "--worker-instance=" . $instance->InstanceID,
                    "--worker-name=" . $instance->Name]
                );
                $instance->PingTimer = new Timer();
                $instance->PingTimer->setMaxTimeLogs(100);

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

            $instance->StartTimestamp = time();
            $instance->Process->start(function ($type, $buffer) use ($instance, $log_handler) {
                if($instance->DisplayOutput == false)
                    return;


                $buffer_split = implode("\n", explode("\r\n", $buffer));
                $buffer_split = explode("\n", $buffer_split);

                foreach($buffer_split as $item)
                {
                    if(strlen($item) == 0)
                        continue;

                    switch(strtolower($type))
                    {
                        case "out":
                        case StdType::STDOUT:
                            $log_handler->log(EventType::INFO, $item, "instance-" . $instance->InstanceID);
                            break;

                        case "err":
                        case StdType::STDERR:
                            $log_handler->log(EventType::ERROR, $item, "instance-" . $instance->InstanceID);
                            break;
                    }
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

            if(isset($this->monitoringTimestamps[$name]) == false)
                $this->monitoringTimestamps[$name] = time();

            if(isset($this->resourceWarningTimestamps[$name]) == false)
                $this->resourceWarningTimestamps[$name] = 0;

            $workerStatisticsResults = [];

            /** @var WorkerInstance $instance */
            foreach(self::$workerInstances[$name] as $instance)
            {
                // Check the running state of the worker
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
                }

                /**
                // Start running statistics after 10 seconds
                if((time() - $instance->StartTimestamp) > 10)
                {
                    // Check the running state of the worker client
                    $workerStatistics = new WorkerStatisticsResults($instance);

                    try
                    {
                        $instance->PingTimer->start();
                        if((bool)@$this->backgroundWorker->getClient()->getGearmanClient()->doHigh($instance->MonitoringFunctionName, WorkerMonitorCommands::PING) == false)
                        {
                            $instance->PingTimer->stop();
                            continue;
                            //throw new WorkerException("The worker returned an unexpected response to the ping command");
                        }
                        else
                        {
                            $instance->PingTimer->stop();
                            $workerStatistics->PingResponseDuration = $instance->PingTimer->getDuration();

                            try
                            {
                                $workerStatistics->PingAvgResponseDuration = $instance->PingTimer->getAverageDuration();
                            }
                            catch(DurationNotAvailableException)
                            {
                                $workerStatistics->PingAvgResponseDuration = $instance->PingTimer->getDuration();
                            }

                            $workerStatistics->MemoryUsage = $this->backgroundWorker->getClient()->getGearmanClient()->doNormal(
                                $instance->MonitoringFunctionName, WorkerMonitorCommands::GET_MEMORY_USAGE);

                            $workerStatistics->RealMemoryUsage = $this->backgroundWorker->getClient()->getGearmanClient()->doNormal(
                                $instance->MonitoringFunctionName, WorkerMonitorCommands::GET_REAL_MEMORY_USAGE);

                            $workerStatistics->Uptime = $this->backgroundWorker->getClient()->getGearmanClient()->doNormal(
                                $instance->MonitoringFunctionName, WorkerMonitorCommands::GET_UPTIME);

                            $workerStatisticsResults[] = $workerStatistics;
                        }
                    }
                    catch(GearmanException | WorkerException $e)
                    {
                        //$this->backgroundWorker->getLogHandler()->log(EventType::WARNING, "Worker " . $instance->InstanceID . " ping failed, " . $e->getMessage(), get_class($this));
                        $instance->PingTimer->cancel();
                    }
                }
                 **/
            }
            /**
            // Start doing StabilityChecks
            if(count($workerStatisticsResults) > 0)
            {
                $collectedData = [
                    "ping_times" => [],
                    "avg_ping_times" => [],
                    "memory_usage" => [],
                    "real_memory_usage" => []
                ];

                foreach($workerStatisticsResults as $workerStatisticsResult)
                {
                    $collectedData["ping_times"][] = $workerStatisticsResult->PingResponseDuration->getNanoseconds();
                    $collectedData["avg_ping_times"][] = $workerStatisticsResult->PingAvgResponseDuration->getNanoseconds();
                    $collectedData["memory_usage"][] = $workerStatisticsResult->MemoryUsage;
                    $collectedData["real_memory_usage"][] = $workerStatisticsResult->RealMemoryUsage;
                }

                $Ping = Duration::fromNanoseconds(array_sum($collectedData["ping_times"]) / count($collectedData["ping_times"]));
                $PingAvg = Duration::fromNanoseconds(array_sum($collectedData["avg_ping_times"]) / count($collectedData["avg_ping_times"]));
                $MemoryAvg = array_sum($collectedData["memory_usage"]) / count($collectedData["memory_usage"]);
                $MemoryTotal = array_sum($collectedData["memory_usage"]);
                $RealMemoryAvg = array_sum($collectedData["real_memory_usage"]) / count($collectedData["real_memory_usage"]);
                $RealMemoryTotal = array_sum($collectedData["real_memory_usage"]);

                // Calculate busy workers
                $collectedData["busy_workers"] = 0;
                $collectedData["lazy_workers"] = 0;
                foreach($collectedData["avg_ping_times"] as $ping_time)
                {
                    if(Duration::fromNanoseconds($ping_time)->getMilliseconds() > 100)
                    {
                        $collectedData["lazy_workers"] += 1;
                    }
                    else
                    {
                        $collectedData["busy_workers"] += 1;
                    }
                }

                if($collectedData["busy_workers"] >  $collectedData["lazy_workers"] && (time() - $this->resourceWarningTimestamps[$name]) > 60)
                {
                    $percentage = (($collectedData["lazy_workers"] / ($collectedData["lazy_workers"] + $collectedData["busy_workers"])) * 100);
                    $this->backgroundWorker->getLogHandler()->log(EventType::WARNING, "The Avg. response time from workers is " . $PingAvg->getMilliseconds() . "ms, $percentage% of available resources are being used, consider increasing worker count.", get_class($this));
                    $this->resourceWarningTimestamps[$name] = time();
                }

                // Print out statistics report
                if((time() - $this->monitoringTimestamps[$name]) > 60)
                {
                    $StatisticsReport =
                        "Reports from %a worker(s) >> " .
                        "Ping %bmsms | Avg Ping %cmsms | " .
                        "Avg Mem. %dh, Total %dth | Avg Real Mem. %eh, Total %eth";

                    $StatisticsReport = str_ireplace("%a", count($workerStatisticsResults), $StatisticsReport);

                    $StatisticsReport = str_ireplace("%bms", $Ping->getMilliseconds(), $StatisticsReport);
                    $StatisticsReport = str_ireplace("%cms", $PingAvg->getMilliseconds(), $StatisticsReport);

                    $StatisticsReport = str_ireplace("%dh", Converter::readableBytes($MemoryAvg), $StatisticsReport);
                    $StatisticsReport = str_ireplace("%db", $MemoryAvg, $StatisticsReport);
                    $StatisticsReport = str_ireplace("%dth", Converter::readableBytes($MemoryTotal), $StatisticsReport);
                    $StatisticsReport = str_ireplace("%dtb", $MemoryTotal, $StatisticsReport);

                    $StatisticsReport = str_ireplace("%eh", Converter::readableBytes($RealMemoryAvg), $StatisticsReport);
                    $StatisticsReport = str_ireplace("%eb", $RealMemoryAvg, $StatisticsReport);
                    $StatisticsReport = str_ireplace("%eth", Converter::readableBytes($RealMemoryTotal), $StatisticsReport);
                    $StatisticsReport = str_ireplace("%etb", $RealMemoryTotal, $StatisticsReport);

                    $this->backgroundWorker->getLogHandler()->log(EventType::INFO, $StatisticsReport, get_class($this));
                    $this->monitoringTimestamps[$name] = time();
                }
            }
             **/
        }


        /**
         * Monitors the instances and restarts them when necessary and preforms stability checks if enabled
         *
         * @param string $name
         */
        public function monitor_loop(string $name)
        {
            while(true)
            {
                $this->monitor($name);
                usleep(500000);
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