<?php


namespace commands;


use Biz\User\CurrentUser;
use Biz\User\Service\UserService;
use support\ServiceKernel;
use support\utils\ShellColorUtil;
use Illuminate\Database\Capsule\Manager as Db;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SystemCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('system:init')
            ->addArgument('nickname', InputArgument::OPTIONAL, '初始账号的nickname')
            ->addArgument('password', InputArgument::OPTIONAL, '初始账号的password')
            ->addArgument('email', InputArgument::OPTIONAL, '初始账号的email')
            ->setDescription('系统初始化');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>开始初始化系统</info>');
        try {
            $adminUser = $this->makeAdminUser($input);
            $user = $this->initAdminUser($adminUser, $output);
            $this->getUserService()->initSystemUsers();
            $output->writeln('<info>初始化系统完毕</info>');
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getTraceAsString()}</error>");
        }


        return 0;
    }

    protected function initAdminUser($fields, OutputInterface $output)
    {
        $output->write("  创建管理员帐号:{$fields['email']}, 密码：{$fields['password']}   ");
        $fields['emailVerified'] = 1;

        $user = $this->getUserService()->getUserByEmail($fields['email']);

        if (empty($user)) {
            $user = $this->getUserService()->register($fields);
        }

        $user['roles'] = array('ROLE_PARTER_ADMIN', 'ROLE_SUPER_ADMIN');
        $user['currentIp'] = '127.0.0.1';

        $currentUser = new CurrentUser();
        $currentUser->fromArray($user);
        ServiceKernel::instance()->setCurrentUser($currentUser);

        $this->getUserService()->changeUserRoles($user['id'], array('ROLE_PARTER_ADMIN','ROLE_SUPER_ADMIN'), $currentUser);

        $output->writeln(' ...<info>成功</info>');

        return $this->getUserService()->getUser($user['id']);
    }

    protected function makeAdminUser(InputInterface $input)
    {
        $nickname = $input->getArgument('nickname');
        $password = $input->getArgument('password');
        $email = $input->getArgument('email');

        if (!empty($nickname) && !empty($password) && !empty($email)) {
            $adminUser = [
                'email' => $email,
                'nickname' => $nickname,
                'password' => $password,
            ];
        } else {
            $adminUser = [
                'email' => 'superAdmin@boyuntong.net',
                'nickname' => 'admin',
                'password' => 'boyuntong',
            ];
        }

        $adminUser['createdIp'] = '127.0.0.1';

        return $adminUser;
    }

    private function makePassword($password, $salt)
    {
        return base64_encode(hash_hmac('sha256', $password, $salt, true));
    }

    private function checkPassword($password, $hashedValue)
    {
        return strlen($hashedValue) === 0 ? false : password_verify($password, $hashedValue);
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->getBiz()->service('User:UserService');
    }
}