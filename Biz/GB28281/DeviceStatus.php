<?php


namespace Biz\GB28281;

use support\exception\InvalidArgumentException;

class DeviceStatus extends \stdClass
{
    const STATUS_ONLINE = 1;

    const STATUS_UNKNOWN = 0;

    const STATUS_OFFLINE = -1;

    /**
     * @var string
     */
    private $cmdType;
    /**
     * @var int
     */
    private $sn;

    /**
     * @var string
     */
    private $deviceID;

    /**
     * @var string
     */
    private $result;

    /**
     * @var string
     */
    private $online;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $encode;

    /**
     * @var string
     */
    private $record;

    /**
     * @var string
     */
    private $deviceTime;

    /**
     * @var array
     */
    private $alarmStatus;


    public function __construct($args)
    {
        $length = count($args);
        if ($length < 8) {
            throw new InvalidArgumentException('DeviceStatus Class Argument Error, must be has:cmdType, sn, deviceID, result, online, status, encode, deviceTime, alarmStatus');
        }

        if ($length === 10) {
            list($this->cmdType, $this->sn, $this->deviceID, $this->result, $this->online, $this->status, $this->encode, $this->record, $this->deviceTime, $this->alarmStatus) = $args;
        } else {
            list($this->cmdType, $this->sn, $this->deviceID, $this->result, $this->online, $this->status, $this->deviceTime, $this->alarmStatus) = $args;
        }
    }

    /**
     * @return string
     */
    public function getCmdType()
    {
        return $this->cmdType;
    }

    /**
     * @return int
     */
    public function getSn()
    {
        return $this->sn;
    }

    /**
     * @return string
     */
    public function getDeviceID()
    {
        return $this->deviceID;
    }

    /**
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return string
     */
    public function getOnline()
    {
        return $this->online;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getEncode()
    {
        return $this->encode;
    }

    /**
     * @return string
     */
    public function getDeviceTime()
    {
        return $this->deviceTime;
    }

    /**
     * @return array
     */
    public function getAlarmStatus()
    {
        return $this->alarmStatus;
    }

    public function getStatusCode()
    {
        if ($this->getIsOnline()) {
            return self::STATUS_ONLINE;
        }

        return self::STATUS_OFFLINE;
    }

    public function getIsOnline()
    {
        return $this->online === "ONLINE";
    }

    public function getKeepTime()
    {
        return !$this->deviceTime ? time() : strtotime($this->deviceTime);
    }

    public function __toString()
    {
        return json_encode([
            'CmdType' => $this->cmdType,
            'SN' => $this->sn,
            'DeviceID' => $this->deviceID,
            'Result' => $this->result,
            'Online' => $this->online,
            'Status' => $this->status,
            'Encode' => $this->encode,
            'Record' => $this->record,
            'DeviceTime' => $this->deviceTime,
            'Alarmstatus' => $this->alarmStatus,
        ]);
    }
}