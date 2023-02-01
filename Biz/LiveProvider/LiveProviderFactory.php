<?php


namespace Biz\LiveProvider;


use Codeages\Biz\Framework\Context\Biz;
use Codeages\Biz\Framework\Service\Exception\NotFoundException;

class LiveProviderFactory
{
    protected $biz;

    public function __construct(Biz $biz)
    {
        $this->biz = $biz;
    }

    public function __destruct()
    {
        $this->biz = null;
    }

    public function createLiveProvider($type)
    {
        $liveProviderType = $this->getLiveProviderType($type);

        if (empty($this->biz->offsetGet($liveProviderType))) {
            throw new NotFoundException("Live Provider strategy {$liveProviderType} does not exist");
        }

        return $this->biz->offsetGet($liveProviderType);
    }

    protected function getLiveProviderType($type)
    {
        return 'live_provider.' . $type;
    }
}