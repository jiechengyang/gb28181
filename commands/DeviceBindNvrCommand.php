<?php


namespace commands;


use Biz\VideoChannels\Service\VideoChannelsService;
use Biz\VideoRecorder\Service\VideoRecorderService;
use support\utils\ArrayToolkit;
use support\utils\ShellColorUtil;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeviceBindNvrCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            // 命令的名称 （"php console_command" 后面的部分）
            ->setName('videoRecorder:bind-ipc')
            // 运行 "php console_command list" 时的简短描述
            ->setDescription('合作方绑定设备')
            ->setHelp('合作方绑定设备，默认绑定前num个设备，如果num为all则表示所有设备')
            // 配置一个参数
            ->addArgument('device_id', InputArgument::REQUIRED, '设备ID')
            ->addArgument('num', InputArgument::REQUIRED, '设备数量');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $deviceId = $input->getArgument('device_id');
        $num = $input->getArgument('num');
        $recorder = $this->getVideoRecorderService()->getVideoRecorderByDeviceId($deviceId);
        if (empty($recorder)) {
            $output->writeln(ShellColorUtil::showError("录像机不存在"));
            return false;
        }

        $this->bindIpc($recorder, $num);
        $output->writeln(ShellColorUtil::showInfo("绑定成功"));

        return true;

    }

    protected function bindIpc($recorder, $num)
    {
        $conditions = ['recorderId' => 0];
        if ('all' === $num) {
            return $this->getVideoChannelsService()->batchUpdateRecorderId($conditions, $recorder['id']);
        }

        $num = intval($num);
        $videoChannels = $this->getVideoChannelsService()->searchVideoChannels($conditions, ['id' => 'ASC'], 0, $num, ['id']);
        $videoChannelsIds = ArrayToolkit::index($videoChannels, 'id');

        return $this->getVideoChannelsService()->batchUpdateRecorderId([
            'recorderId' => 0,
            'ids' => $videoChannelsIds
        ], $recorder['id']);
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