<?php


    namespace BackgroundWorker\Exceptions;


    use Exception;
    use Throwable;

    /**
     * Class NoWorkersRunningException
     * @package BackgroundWorker\Exceptions
     */
    class NoWorkersRunningException extends Exception
    {
        /**
         * @var Throwable|null
         */
        private ?Throwable $previous;

        /**
         * NoWorkersRunningException constructor.
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