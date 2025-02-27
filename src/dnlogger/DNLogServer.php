<?php

namespace dnlogger;

use ide\Ide;
use ide\Logger;
use php\io\IOException;
use php\lang\Thread;
use php\net\ServerSocket;

class DNLogServer
{
    private static $host = '0.0.0.0';
    public static $state = false;
    /**
     * @var \php\net\ServerSocket
     */
    public static $server;

    public static function start($port, $messageCallback)
    {
        self::$server = new ServerSocket();
        self::$server->bind(self::$host, $port);

        $th = new Thread(function () use ($port, $messageCallback) {
            try {
                self::$state = true;
                while ($client = self::$server->accept()) {

                    // Logger::error('Client connected');

                    $input = $client->getInput();
                    /// $length = ord($input->read(1));
                    // $line = $input->read($length);
                    $line = "";

                    while (($chr = $input->read(1)) !== "\n" && $chr != null) {
                        $line .= $chr;
                    }

                    // Logger::error($line);
                    // Logger::error("len:".$length);

                    uiLater(function () use ($messageCallback, $line) {
                        $messageCallback(trim($line));
                    });

                    $client->close();
                }

                self::$state = false;
            } catch (\Exception $e) {
                Logger::error($e->getMessage());
                self::$state = false;
            }
        });
        $th->start();
    }

    public static function stop()
    {
        try {
            self::$server->close();
        } catch (IOException $e) { }
    }
}