<?php


namespace commands;


use Biz\ThirdParty\Service\ThirdPartyService;
use Biz\VideoChannels\Service\VideoChannelsService;
use Biz\VideoRecorder\Service\VideoRecorderService;
use support\utils\ArrayToolkit;
use support\utils\ShellColorUtil;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeviceBindPartnerCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            // 命令的名称 （"php console_command" 后面的部分）
            ->setName('thirdParty:bind-device')
            // 运行 "php console_command list" 时的简短描述
            ->setDescription('合作方绑定设备')
            ->setHelp('合作方绑定设备，默认绑定前num个设备，如果num为all则表示所有设备')
            // 配置一个参数
            ->addArgument('key', InputArgument::REQUIRED, '合作方key')
            ->addArgument('type', InputArgument::REQUIRED, '设备类型：nvr或者ipc')
            ->addArgument('num', InputArgument::REQUIRED, '设备数量');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $input->getArgument('key');
        $type = $input->getArgument('type');
        $num = $input->getArgument('num');
        $thirdPartner = $this->getThirdPartyService()->getThirdPartyByAppKey($key);
        if (empty($thirdPartner)) {
            $output->writeln(ShellColorUtil::showError("合作方不存在"));
            return false;
        }

        if (!in_array($type, ['nvr', 'ipc'])) {
            $output->writeln(ShellColorUtil::showError("设备类型不存在"));
            return false;
        }

        $this->{sprintf("bind%s", ucfirst($type))}($thirdPartner, $num);

        $output->writeln(ShellColorUtil::showInfo("绑定成功"));

        return true;

    }

    protected function bindNvr($thirdPartner, $num)
    {
        $conditions = ['parterId' => 0];
        if ('all' === $num) {
           return $this->getVideoRecorderService()->batchUpdateParterId($conditions, $thirdPartner['id']);
        }

        $num = intval($num);

        $videoRecorders = $this->getVideoRecorderService()->searchRecorders($conditions, ['id' => 'ASC'], 0, $num, ['id']);
        $videoRecorderIds = ArrayToolkit::index($videoRecorders, 'id');

        return $this->getVideoRecorderService()->batchUpdateParterId([
            'parterId' => 0,
            'ids' => $videoRecorderIds
        ], $thirdPartner['id']);
    }

    protected function bindIpc($thirdPartner, $num)
    {
        $conditions = ['parterId' => 0];
        if ('all' === $num) {
            return $this->getVideoChannelsService()->batchUpdatePartnerId($conditions, $thirdPartner['id']);
        }

        $num = intval($num);

        $videoChannels = $this->getVideoChannelsService()->searchVideoChannels($conditions, ['id' => 'ASC'], 0, $num, ['id']);
        $videoChannelsIds = ArrayToolkit::index($videoChannels, 'id');

        return $this->getVideoChannelsService()->batchUpdatePartnerId([
            'parterId' => 0,
            'ids' => $videoChannelsIds
        ], $thirdPartner['id']);
    }

    /**
     * @return ThirdPartyService
     * @throws \Webman\Exception\NotFoundException
     */
    protected function getThirdPartyService()
    {
        return $this->getBiz()->service('ThirdParty:ThirdPartyService');
    }

    /**
     * @return VideoChannelsService
     * @throws \Webman\Exception\NotFoundException
     */
    protected function getVideoChannelsService()
    {
        return $this->getBiz()->service('VideoChannels:VideoChannelsService');
    }

    /**
     * @return VideoRecorderService
     * @throws \Webman\Exception\NotFoundException
     */
    protected function getVideoRecorderService()
    {
        return $this->getBiz()->service('VideoRecorder:VideoRecorderService');
    }
}