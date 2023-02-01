<?php


namespace commands;


use Biz\ThirdParty\Exception\ThirdPartyException;
use Biz\ThirdParty\Service\ThirdPartyService;
use support\bootstrap\Container;
use support\utils\ShellColorUtil;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ThirdPartyCreateCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            // 命令的名称 （"php console_command" 后面的部分）
            ->setName('thirdParty:create')
            // 运行 "php console_command list" 时的简短描述
            ->setDescription('创建合作方')
            // 运行命令时使用 "--help" 选项时的完整命令描述
            ->setHelp('生成唯一合作方...')
            // 配置一个参数
            ->addArgument('name', InputArgument::REQUIRED, '合作方名称')
            ->addArgument('live_providers', null, '云监控提供平台;多个以,隔开')
            ->addArgument('params', null, '云监控平台参数');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $appName = $input->getArgument('name');
        $liveProviders = explode(',', $input->getArgument('live_providers'));
        empty($liveProviders) && $liveProviders = ['BLive'];
        $params = json_decode($input->getArgument('params', ''), true);
        try {
            $thirdParty = $this->getThirdPartyService()->createThirdParty([
                'partner_name' => $appName,
                'live_providers' => $liveProviders,
                'params' => $params,
            ]);
            if (empty($thirdParty)) {
                throw new \Exception("合作方{$appName}创建失败");
            }
            $output->writeln(ShellColorUtil::showInfo("合作方{$appName}创建成功"));
        } catch (ThirdPartyException $exception) {
            $output->writeln(ShellColorUtil::showError($exception->getMessage()));
        } catch (\Exception $exception) {
            $output->writeln(ShellColorUtil::showError($exception->getMessage()));
        }
    }

    /**
     * @return ThirdPartyService
     * @throws \Webman\Exception\NotFoundException
     */
    protected function getThirdPartyService()
    {
        return $this->getBiz()->service('ThirdParty:ThirdPartyService');
    }
}