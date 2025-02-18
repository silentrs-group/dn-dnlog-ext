<?php

namespace dnlogger;

use php\framework\Logger;
use php\io\IOException;
use php\lib\str;
use php\net\Socket;
use php\net\SocketException;
use php\time\Time;

/**
 * Class Log - Provides a simple logging utility for application logs.
 */
final class Log
{
    /**
     * @var string Host address of the log server.
     */
    private static $host = '127.0.0.1';

    /**
     * @var int Port number of the log server.
     */
    private static $port = 11394;

    /**
     * @var int Timeout for socket connection in milliseconds.
     */
    private static $timeout = 1000;

    /**
     * @var bool Whether to use dots instead of backslashes in the namespace.
     */
    public static $useDotsInNamespace = true;

    /**
     * Logs a debug message.
     *
     * @param string $message The log message.
     * @param string|null $tag Optional tag for the log message.
     */
    public static function d($message, $tag = null)
    {
        self::log('DEBUG', $message, $tag);
    }

    /**
     * Logs an info message.
     *
     * @param string $message The log message.
     * @param string|null $tag Optional tag for the log message.
     */
    public static function i($message, $tag = null)
    {
        self::log('INFO', $message, $tag);
    }

    /**
     * Logs a warning message.
     *
     * @param string $message The log message.
     * @param string|null $tag Optional tag for the log message.
     */
    public static function w($message, $tag = null)
    {
        self::log('WARN', $message, $tag);
    }

    /**
     * Logs an error message.
     *
     * @param string $message The log message.
     * @param string|null $tag Optional tag for the log message.
     */
    public static function e($message, $tag = null)
    {
        self::log('ERROR', $message, $tag);
    }

    /**
     * Sends a message to the log server.
     *
     * @param string $message The message to send.
     * @throws \php\io\IOException|\php\net\SocketException
     */
    private static function sendMessage($message)
    {
        $length = strlen($message);

        $client = new Socket();
        $client->setSoTimeout(self::$timeout);
        $client->connect(self::$host, self::$port);
        // $client->getOutput()->write(chr($length) . $message . "\r\n", $length + 3);
        $client->getOutput()->write($message . "\n", $length+1);
    }

    /**
     * Logs a message at the specified level.
     *
     * @param string $level The log level (DEBUG, INFO, WARN, ERROR).
     * @param string $message The log message.
     * @param string|null $tag Optional tag for the log message.
     */
    private static function log($level, $message, $tag = null)
    {
        $args = [];
        array_push($args, $level);
        array_push($args, Time::now()->toString("HH:mm:ss"));
        array_push($args, self::getCalledClass());
        array_push($args, base64_encode($message));


        try {
            self::sendMessage(json_encode($args));
        } catch (IOException|SocketException $e) {
            self::logWithUseLogger("Error while connecting: " . $e->getMessage());
        }
    }

    /**
     * Logs an error message using the Logger class if available.
     *
     * @param string $message The log message.
     */
    private static function logWithUseLogger($message)
    {
        if (!class_exists(Logger::class)) {
            echo "$message\n";
            return;
        }

        Logger::error($message);
    }

    /**
     * Retrieves the calling class name, optionally replacing backslashes with dots.
     *
     * @return string|null The calling class name or null if not available.
     */
    private static function getCalledClass()
    {
        $backtrace = debug_backtrace();

        foreach ($backtrace as $trace) {
            if ($trace["class"] !== __CLASS__ && $trace["class"] !== null) {
                return (self::$useDotsInNamespace) ?
                    str::replace($trace["class"], '\\', '.') : $trace["class"];
            }
        }

        return null;
    }
}