<?php


    namespace BackgroundWorker\Exceptions;


    use Exception;
    use Throwable;

    /**
     * Class WorkerException
     * @package BackgroundWorker\Exceptions
     */
    class WorkerException extends Exception
    {
        /**
         * @var Throwable|null
         */
        private ?Throwable $previous;

        /**
         * WorkerException constructor.
         * @param string $message
         * @param int $code
         * @param Throwable|null $previous
         */
        public function __construct($message = "", $code = 0, Throwable $previous = null)
        {
            parent::__construct($message, $code, $previous);
            $this->message = $message;
            $this->code = $code;
            $this->previous = $previous;
        }
    }