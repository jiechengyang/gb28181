<?php


namespace commands;


use Biz\SipClientSdk\SipHttpClient;
use Biz\ThirdParty\Service\ThirdPartyService;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestAkSkApiCommand extends BaseCommand
{
    const TEST_APP_KEY = '5i4J9fb8Uqn8r_EEFW';

    protected function configure()
    {
        $this
            // 命令的名称 （"php console_command" 后面的部分）
            ->setName('ak-sk:test')
            // 运行 "php console_command list" 时的简短描述
            ->setDescription('开放api签名认证测试')
            ->addArgument('uri', InputArgument::REQUIRED, '接口地址')
            // 运行命令时使用 "--help" 选项时的完整命令描述
            ->setHelp('开放api签名认证测试...');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $uri = $input->getArgument('uri');
        $method = sprintf("test%s", str_replace(" ", "", ucwords(str_replace("/", " ", $uri))));

        if (method_exists($this, $method)) {
            /** @var  $response ResponseInterface */
            $response = $this->$method();
            $result = $response->getBody()->getContents();
            $output->writeln("测试结果为：");
            var_dump($result);
            return 0;
        }

        $output->writeln("暂未找到接口：${uri}的测试方法");

        return 0;
    }

    protected function testLiveAddress()
    {
        return $this->getSipClientSdk(self::TEST_APP_KEY)->get('live/address', [
            'query' => ['deviceId' => '1111-2222-3333-4444']
        ]);
    }

    /**
     * @param $appKey
     * @return SipHttpClient
     * @throws \Webman\Exception\NotFoundException
     */
    protected function getSipClientSdk($appKey)
    {
        $thirdParty = $this->getThirdPartyService()->getThirdPartyByAppKey($appKey);
        if (empty($thirdParty)) {
            throw new \Exception("合作方不存在，无法获取接口");
        }

        $sipHttpClient = new SipHttpClient($appKey, $thirdParty['partner_sceret'], ['base_uri' => 'http://127.0.0.1:8787/sip/']);
        $sipHttpClient->setDebug(true);

        return $sipHttpClient;
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