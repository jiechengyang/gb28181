<?php


namespace Biz\User\Dao\Impl;


use Biz\User\Dao\TokenDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class TokenDaoImpl extends GeneralDaoImpl implements TokenDao
{
    protected $table = 'smp_user_token';

    public function getByToken($token)
    {
        $sql = "SELECT * FROM {$this->table} WHERE token = ? LIMIT 1";

        return $this->db()->fetchAssoc($sql, [$token]) ?: null;
    }

    public function findByUserIdAndType($userId, $type)
    {
        return $this->findByFields(['userId' => $userId, 'type' => $type]);
    }

    public function destroyTokensByUserId($userId)
    {
        return $this->db()->delete($this->table, ['userId' => $userId]);
    }

    public function getByType($type)
    {
        $sql = "SELECT * FROM {$this->table} WHERE type = ?  and expiredTime > ? order  by createdTime DESC  LIMIT 1";

        return $this->db()->fetchAssoc($sql, [$type, time()]) ?: null;
    }

    public function deleteTopsByExpiredTime($expiredTime, int $limit)
    {
        $sql = "DELETE FROM {$this->table} WHERE expiredTime < ? LIMIT {$limit} ";

        return $this->db()->executeQuery($sql, [$expiredTime]);
    }

    public function deleteByTypeAndUserId($type, $userId)
    {
        return $this->db()->delete($this->table, ['type' => $type, 'userId' => $userId]);
    }

    public function declares()
    {
        return [
            'serializes' => [
                'data' => 'php'
            ],
            'conditions' => [
                'type = :type'
            ],
        ];
    }
}