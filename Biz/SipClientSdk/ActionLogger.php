<?php


namespace Biz\SipClientSdk;


class ActionLogger
{
    private static $_instance = null;

    const DEFAULT_LOG_FILE_NAME = 'action.log';


    private $maxFileSize = 1024 * 1024 * 5;

    private $path;

    private $logFileName;


    public function __construct(string $path, string $logFileName = '', int $maxFileSize = null)
    {
        if (empty($path)) {
            throw new \Exception("The Log Path Is Empty");
        }

        $this->logFileName = empty($logFileName) ? self::DEFAULT_LOG_FILE_NAME : $logFileName;
        !empty($maxFileSize) && $this->maxFileSize = $maxFileSize;
        $this->path = str_replace('\\', '/', $path);
    }


    /**
     * @param array ...$args
     * @return null|static
     */
    public static function getInstance(...$args)
    {
        if (is_null(self::$_instance)) {
            // TODO: new static and new self 区别在于存在继承时，new static决定在于当前调用，而new self 在于类本身
            self::$_instance = new static(...$args);
        }

        return self::$_instance;
    }

    /**
     * @param $contents
     */
    public function write($contents, $end = false)
    {
        $str = '---------------------- Time: ' . date('Y-m-d H:i:s') . ' ----------------------' . "\n\n";
        $str .= $contents . "\n\n";
        if ($end) {
            $str .= '---------------------- End Block  ----------------------' . "\n";
        }
        $logFile = $this->isBack();
        $fp = fopen($logFile, 'a+');
        fwrite($fp, $str);
        fclose($fp);
    }

    /**
     * @param $path
     * @param $file
     * @return bool
     */
    private function backup($path, $file)
    {
        $newFile = $path . 'action' . '.bak';
        $i = 1;
        while (is_file($newFile)) {
            $newFile = $path . 'action' . $i++ . '.bak';
        }

        return rename($file, $newFile);
    }

    /**
     * @return string
     */
    private function isBack()
    {
        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
        }

        $file = $this->path . DIRECTORY_SEPARATOR . $this->logFileName;
        if (!is_file($file)) {
            touch($file);
            return $file;
        }

        $fileSize = filesize($file);
        clearstatcache(true, $file);
        if ($this->maxFileSize >= $fileSize) {
            return $file;
        }

        $backFile = $this->backup($this->path, $file);
        if ($backFile) {
            touch($file);
            return $file;
        }

        return $file;
    }
}