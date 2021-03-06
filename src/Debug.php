<?php
/**
 * @copyright (c) SAD-Systems, 2019. All Rights Reserved.
 * @link      http://sad-systems.ru
 * @license   MIT
 */

namespace digger\firetools;

/**
 * A most simple but powerful debug utility.
 * 
 * Static library.
 *
 * PHP Version  7.1
 *
 * @package     digger\firetools
 * @category    debug
 *
 * @author      MrDigger <mrdigger@mail.ru>
 * @created     07.02.2019
 */
class Debug
{
    /** 
     * Enables or disables date time stamp in the log messages 
     * @var bool 
     */
    public static $enableTime = false;

    /** 
     * Defines the default path for log file
     * @var string  
     */
    public static $defaultLogFilePath = '/tmp';

    /**
     * Defines the name of the constant that store a path for log files 
     * @var string 
     */
    public static $constantNameForLogFilePath = 'TMP_PATH';

    /** 
     *  Defines the log file name  
     *
     *  Possible options:
     *  
     *  * The log file full name.
     *  * Or just a name of the file.
     *     In this case full name will be created by using the path is defined by constant with name stored in `$constantNameForLogFilePath`.
     *     If no constant is defined the default path (`$defaultLogFilePath`) will be used.
     *     
     * @var string
     */
    public static $logFileName = 'debug';

    /**
     * Saves a log data of any type.
     *
     * Example of using:
     *
     * ```php
     *      Debug::log();                                 //<--- The arguments of calling function will be added to the log
     *      Debug::log('Hello');                          //<--- 'Hello' will be added to the log
     *      Debug::log('My data', 123, 'test', [1,2,3]);  //<--- All this data will be added to the log
     *      Debug::log(['My' => 'Hash']);                 //<--- The array will be added to the log
     * ```
     * 
     * @param mixed ...$args
     */
    public static function log(...$args)
    {
        $trace = debug_backtrace();

        $targetInfoIndex = count($trace) > 1 ? 1 : 0;
        $targetInfo      = $trace[ $targetInfoIndex ];
        $targetFile      = basename($trace[0]['file']);
        $lineNumber      = $trace[0]['line'];
        $targetClass     = $targetInfo['class'] ?? '';
        $targetType      = $targetInfo['type']  ?? '';
        $targetFunction  = $targetInfo['function'] . '()';
        if ($targetInfoIndex == 0) {
            $targetClass    = '';
            $targetType     = '';
            $targetFunction = $targetFile;
        }

        $target = $targetClass . $targetType . $targetFunction . ': ' . $lineNumber;

        $data = !empty($args) ? $args : $targetInfo['args'];
        if (count($data) == 1) { $data = $data[0]; }
        if (empty($data)) { $data = null; }

        $content = "=== [ LOG ] === [ $target ]: " . print_r($data,1);
        static::save($content);
    }

    /**
     * Sets handlers to catch program errors and exceptions and save it to the log.
     *
     * Example of using:
     * ```php
     *      Debug::catchErrors(); // <--- At any place of code before a potential error
     * ```
     */
    public static function catchErrors()
    {
        register_shutdown_function(function()                                    { static::shutdownHandler(); });
        set_error_handler         (function($errno, $errstr, $errfile, $errline) { static::errorHandler($errno, $errstr, $errfile, $errline);});
        set_exception_handler     (function($e)                                  { static::exceptionHandler($e); });
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Handlers
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Registers an exception handler
     * 
     * @param \Error $e The error object
     */
    protected static function exceptionHandler(\Error $e)
    {
        $content = "=== [ EXCEPTION ({$e->getCode()}) ] === [ {$e->getFile()}: {$e->getLine()} ]: {$e->getMessage()}\n{$e->getTraceAsString()}";
        static::save($content);
    }

    /**
     * Registers an error handler
     * 
     * @param int    $errno 
     * @param string $errstr 
     * @param string $errfile 
     * @param int    $errline
     */
    protected static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $content = "=== [ ERROR ($errno) ] === [ $errfile: $errline ]: $errstr";
        static::save($content);
    }

    /**
     * Registers a shutdown handler
     */
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

    /** 
     * Cached log file name
     * @var string  
     */
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
     * Returns the current time stamp
     *
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