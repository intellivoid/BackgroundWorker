<?php


    namespace BackgroundWorker\Utilities;

    /**
     * Class Converter
     * @package BackgroundWorker\Utilities
     */
    class Converter
    {
        /**
         * Calculates the internal ID for the worker instance
         *
         * @param string $name
         * @param string $instance_id
         * @return string
         */
        public static function calculateWorkerInternalId(string $name, string $instance_id): string
        {
            return "w_mon_" . hash("adler32", $name . $instance_id);
        }

        /**
         * Converts bytes to human readable storage representation
         *
         * @param int $bytes
         * @return string
         */
        public static function readableBytes(int $bytes): string
        {
            $i = floor(log($bytes) / log(1024));

            $sizes = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

            return sprintf('%.02F', $bytes / pow(1024, $i)) * 1 . ' ' . $sizes[$i];
        }

    }