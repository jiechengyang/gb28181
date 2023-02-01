<?php

namespace commands;


use Biz\AkStreamSdk\AkStreamSdk;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 
 * 
 */
class TestZlmApiCommand extends BaseCommand
{

    protected function configure()
    {
        $this
            // 命令的名称 （"php console_command" 后面的部分）
            ->setName('zlm:test-api')
            // 运行 "php console_command list" 时的简短描述
            ->setDescription('ZlmediaKit Open Api Test')
            // 运行命令时使用 "--help" 选项时的完整命令描述
            ->setHelp('需要传入zlm的api名称')
            // 配置一个参数
            ->addArgument('action', InputArgument::REQUIRED, 'zlm api action');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $action = $input->getArgument('action');
        if (!method_exists($this->getAkStreamSdk()->zlmediaKit, $action)) {
            $output->writeln("<error>接口不存在</error>");
            return false;
        }

        list($code, $result, $msg) = $this->getAkStreamSdk()->zlmediaKit->{$action}();
        if (0 === $code) {
            $output->writeln("<info>请求成功</info>");
            echo var_export($result, true);
            return true;
        }

        $output->writeln("<info>请求失败：{$msg}</info>");

        return false;
    }

    /**
     * @return AkStreamSdk
     */
    protected function getAkStreamSdk()
    {
        return self::$biz->offsetGet('sip.ak_stream_sdk');
    }

}