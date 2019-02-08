<?php

namespace digger\firetools;

/**
 * A most simple but powerful debug utility.
 * Static library.
 *
 * PHP Version  7.1
 *
 * @package     debug
 * @category	debug
 *
 * @copyright   2019, (c) SAD-Systems. All Rights Reserved.
 * @author      MrDigger <mrdigger@mail.ru>
 * @link        http://sad-systems.ru
 *
 * @created     07.02.2019
 */
class Debug
{
    /** @var bool Enable or disable date time stamp */
    public static $enableTime = false;

    /** @var string The default path for log file */
    public static $defaultLogFilePath = '/tmp';

    /** @var string The name of the constant that store a path for log files */
    public static $constantNameForLogFilePath = 'TMP_PATH';

    /** @var string - The log file full name.
     *              - Or just a name of the file.
     *                In this case full name will be created by using the path is defined by constant with name stored in `$constantNameForLogFilePath`.
     *                If no constant is defined the default path (`$defaultLogFilePath`) will be used.
     */
    public static $logFileName = 'debug';

    /**
     * Saves a log data of any type.
     *
     * Example of using:
     *      Debug::log();
     *      Debug::log('Hello');
     *      Debug::log('My data', 123, 'test', [1,2,3]);
     *      Debug::log(['My' => 'Hash']);
     *
     * @param mixed ...$args
     */
    public static function log(...$args)
    {
        $trace  = debug_backtrace();
        $target = $trace[1]['class'] . $trace[1]['type'] . $trace[1]['function'] . ': ' . $trace[0]['line'];
        $data   = !empty($args) ? $args : $trace[1]['args'];
        if (count($data) == 1) { $data = $data[0]; }

        $content = "=== [ LOG ] === [ $target ]: " . print_r($data,1);
        static::save($content);
    }

    /**
     * Sets handlers to catch program errors and exceptions and save it to the log.
     *
     * Example of using:
     *      Debug::catch(); // <--- At any place of code before a potential error
     *
     */
    public static function catch()
    {
        register_shutdown_function(function()                                    { static::shutdownHandler(); });
        set_error_handler         (function($errno, $errstr, $errfile, $errline) { static::errorHandler($errno, $errstr, $errfile, $errline);});
        set_exception_handler     (function($e)                                  { static::exceptionHandler($e); });
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Handlers
    // -----------------------------------------------------------------------------------------------------------------

    protected static function exceptionHandler(\Error $e)
    {
        $content = "=== [ EXCEPTION ({$e->getCode()}) ] === [ {$e->getFile()}: {$e->getLine()} ]: {$e->getMessage()}\n{$e->getTraceAsString()}";
        static::save($content);
    }

    protected static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $content = "=== [ ERROR ($errno) ] === [ $errfile: $errline ]: $errstr";
        static::save($content);
    }

    protected static function shutdownHandler()
    {
        if (error_get_last()) {
            $message = error_get_last();
            $content = "=== [ SHUTDOWN ] === : $message";
            static::save($content);
        }
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------------------------------------------------

    protected static $createdLogFileName = null;

    /**
     * Creates the full name for log file
     *
     * @return string The log file full name
     */
    protected static function createLogFileName()
    {
        if ( !static::$logFileName || ( static::$logFileName == basename(static::$logFileName) ) ) {
            $createdLogFileName =
                ( defined(static::$constantNameForLogFilePath) ? constant(static::$constantNameForLogFilePath) : static::$defaultLogFilePath )
                . '/'
                . ( static::$logFileName ? static::$logFileName : 'debug' );
        } else {
            $createdLogFileName = static::$logFileName;
        }
        return $createdLogFileName;
    }

    /**
     * Returns the full name of the log file
     *
     * @return string The log file full name
     */
    protected static function getLogFileName()
    {
        return static::$createdLogFileName ?? ( static::$createdLogFileName = static::createLogFileName() );
    }

    /**
     * @return string Current time stamp
     */
    protected static function getTime(): string
    {
        return date("[ Y.m.d H:i:s ]");
    }

    /**
     * Creates the formatted log string
     *
     * @param  string $text  String with some log data
     *
     * @return string        Final formatted log string
     */
    protected static function createLogMessage(string $text): string
    {
        return (static::$enableTime ? static::getTime() . ' ' . $text : $text) . "\n";
    }

    /**
     * Saves the log content
     *
     * @param  string   $content
     *
     * @return bool|int
     */
    protected static function save(string $content)
    {
        return file_put_contents(static::getLogFileName(), static::createLogMessage($content), FILE_APPEND);
    }

}