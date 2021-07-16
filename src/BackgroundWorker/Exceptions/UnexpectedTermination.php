<?php


    namespace BackgroundWorker\Exceptions;

    use Exception;
    use Throwable;

    /**
     * Class UnexpectedTermination
     * @package BackgroundWorker\Exceptions
     */
    class UnexpectedTermination extends Exception
    {
        /**
         * @var string
         */
        private string $output;

        /**
         * @var string
         */
        private string $error_output;

        /**
         * @var Throwable|null
         */
        private ?Throwable $previous;

        /**
         * UnexpectedTermination constructor.
         * @param string $message
         * @param int $code
         * @param string $output
         * @param string $error_output
         * @param Throwable|null $previous
         */
        public function __construct($message = "", $code = 0, string $output="", string $error_output="", Throwable $previous = null)
        {
            parent::__construct($message, $code, $previous);
            $this->message = $message;
            $this->code = $code;
            $this->output = $output;
            $this->error_output = $error_output;
            $this->previous = $previous;
        }

        /**
         * @return string
         */
        public function getOutput(): string
        {
            return $this->output;
        }

        /**
         * @return string
         */
        public function getErrorOutput(): string
        {
            return $this->error_output;
        }
    }