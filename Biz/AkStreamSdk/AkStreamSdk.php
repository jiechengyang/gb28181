<?php


namespace Biz\AkStreamSdk;


use Biz\AkStreamSdk\Logger\JsonLogger;
use Biz\AkStreamSdk\Resources\MediaServer;
use Biz\AkStreamSdk\Resources\SipServer;
use Biz\AkStreamSdk\Resources\ZlmediaKit;
use Codeages\Biz\Framework\Context\Biz;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use \InvalidArgumentException;

/**
 * Class AkStreamSdk
 *
 * @property MediaServer $mediaServer
 * @property SipServer $sipServer
 * @property ZlmediaKit $zlmediaKit
 *
 * @package Biz\AkStreamSdk
 */
class AkStreamSdk
{
    const MAX_LOG_SIZE = 52428800; //50M

    private $client;

    private $zlmClient;

    private $biz;

    private $config;

    private static $_instance = null;

    public function __construct(Biz $biz, $config)
    {
        $this->config = $config;
        $this->biz = $biz;
        $this->init();
    }

    public function __destruct()
    {
        $this->biz = null;
    }

    /**
     * @param Biz $biz
     * @param $config
     * @return AkStreamSdk|null
     */
    public static function getInstance(Biz $biz, $config)
    {
        if (!self::$_instance instanceof self) {
            return new self($biz, $config);
        }

        return self::$_instance;
    }

    public function __get($api)
    {
        return $this->api($api);
    }

    public function api($name)
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'Resources';
        $name = ucfirst($name);
        $fileName = $path . DIRECTORY_SEPARATOR . $name . '.php';
        if (is_file($fileName)) {
            $className = __NAMESPACE__ . "\\Resources\\Impl\\{$name}Impl";
            if (!class_exists($className)) {
                throw new InvalidArgumentException("{$className} does not exist");
            }

            $reflection = new \ReflectionClass($className);
            if (strpos($name, 'ZlmediaKit') !== false) {
                return $reflection->newInstanceArgs([
                    $this->zlmClient,
                ]);
            }

            return $reflection->newInstanceArgs([
                $this->client,
            ]);
        }

        throw new InvalidArgumentException('Invalid api: "' . $name . '".');
    }

    protected function init()
    {
        if (empty($this->config['api_url'])) {
            throw new InvalidArgumentException('Invalid config: api_url');
        }

        if (empty($this->config['access_key'])) {
            throw new InvalidArgumentException('Invalid config: access_key');
        }

        $this->initAkStreamClient();
        if (!empty($this->config['zlmediakit_api']) && !empty($this->config['zlmediakit_secret'])) {
            $this->initZlmediaKitClient();
        }
    }

    protected function initAkStreamClient()
    {
        $this->client = new HttpClient\Client($this->config);
        $stream = $this->createAKStream();
        $logger = new JsonLogger('ak-stream-sdk', $stream);
        $this->client->setLogger($logger);
    }

    protected function initZlmediaKitClient()
    {
        $this->zlmClient = new HttpClient\ZlmediaKitClient($this->config);
        $stream = $this->createZlmStream();
        $logger = new JsonLogger('zlmediakit-sdk', $stream);
        $this->zlmClient->setLogger($logger);
    }


    protected function createAKStream()
    {
        $logFile = runtime_path() . '/logs/akStreamSdk/' . date('Ym') . '/' . date('d') . '.log';
        if (is_file($logFile)) {
            $fileSize = filesize($logFile);
            clearstatcache(true, $logFile);
            $fileSize > self::MAX_LOG_SIZE && unlink($logFile);
        }

        return new StreamHandler($logFile, Logger::DEBUG, true, 0777);
    }

    protected function createZlmStream()
    {
        $logFile = runtime_path() . '/logs/zlmediaKitSdk/' . date('Ym') . '/' . date('d') . '.log';
        if (is_file($logFile)) {
            $fileSize = filesize($logFile);
            clearstatcache(true, $logFile);
            $fileSize > self::MAX_LOG_SIZE && unlink($logFile);
        }

        return new StreamHandler($logFile, Logger::DEBUG, true, 0777);
    }
}