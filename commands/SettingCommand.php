<?php


namespace commands;


use Biz\Setting\Service\SettingService;
use Biz\SystemLog\Service\SystemLogService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SettingCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            // 命令的名称 （"php console_command" 后面的部分）
            ->setName('setting:ipBackLists')
            // 运行 "php console_command list" 时的简短描述
            ->setDescription('访问IP防护设置')
            // 运行命令时使用 "--help" 选项时的完整命令描述
            ->setHelp('访问IP防护...')
            // 配置一个参数
            ->addArgument('blacklist_ip', InputArgument::REQUIRED, 'IP黑名单:多个IP用,隔开，被加入黑名单的IP将被禁止访问！可使用通配符，例如：202.101.20.*')
            ->addArgument('whitelist_ip', null, 'IP白名单：多个IP用,隔开，只有列表中的IP地址允许访问系统！可使用通配符，例如：202.101.20.*');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $blacklistIp = $input->getArgument('blacklist_ip');
        $whitelistIp = $input->getArgument('whitelist_ip');
        $blacklistIps = explode(',', $blacklistIp);
        $whitelistIps = explode(',', $whitelistIp);
        if ('empty' === $blacklistIp) {
            $this->getSettingService()->delete('blacklist_ip');
            $this->getSystemLogService()->info('system', 'update_settings', '清空IP黑名单', ['whitelist_ip' => []]);
            $output->writeln("清空IP黑名单");
        } elseif (!empty($blacklistIps)) {
            $blacklistIps = array_filter($blacklistIps);
            if (!empty($blacklistIps)) {
                $oldBlackIps = $this->getSettingService()->get('blacklist_ip');
                if (!empty($oldBlackIps['ips'])) {
                    $blacklistIps = array_merge($oldBlackIps, $oldBlackIps);
                }

                $this->getSettingService()->set('blacklist_ip', $blacklistIps);
                $this->getSystemLogService()->info('system', 'update_settings', '更新IP黑名单', ['blacklist_ip' => $blacklistIps]);
                $output->writeln("更新IP黑名单:" . implode(',', $blacklistIps));
            }
        }


        if ('empty' === $whitelistIp) {
            $this->getSettingService()->delete('whitelist_ip');
            $this->getSystemLogService()->info('system', 'update_settings', '清空IP白名单', ['whitelist_ip' => []]);
            $output->writeln("清空IP白名单");
        } elseif (!empty($whitelistIps)) {
            $whitelistIps = array_filter($whitelistIps);
            if (!empty($whitelistIps)) {
                $this->getSettingService()->set('whitelist_ip', $whitelistIps);
                $this->getSystemLogService()->info('system', 'update_settings', '更新IP白名单', ['whitelist_ip' => $whitelistIps]);
                $output->writeln("更新IP白名单:" . implode(',', $whitelistIps));
            }

        }


    }

    /**
     * @return SettingService
     * @throws \Webman\Exception\NotFoundException
     */
    protected function getSettingService()
    {
        return $this->getBiz()->service('Setting:SettingService');
    }

    /**
     * @return SystemLogService
     * @throws \Webman\Exception\NotFoundException
     */
    protected function getSystemLogService()
    {
        return $this->getBiz()->service('SystemLog:SystemLogService');
    }
}