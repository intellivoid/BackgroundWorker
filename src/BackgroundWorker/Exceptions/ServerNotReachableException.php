<?php

    /** @noinspection PhpPropertyOnlyWrittenInspection */

    namespace BackgroundWorker\Exceptions;

    use Exception;
    use Throwable;

    class ServerNotReachableException extends Exception
    {
        /**
         * @var Throwable|null
         */
        private ?Throwable $previous;

        /**
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