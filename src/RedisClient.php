<?php
/**
 * Created by PhpStorm.
 * User: zjw
 * Date: 2017/8/25
 * Time: 下午3:59
 */

namespace monsterhunter\oauth2\redis;

class RedisClient
{
    /**
     * @var \Predis\Client | \Redis
     */
    public $client;

    /**
     * @var array
     */
    public $params;

    /**
     * RedisClient constructor.
     * @param array $params   ['host' => '127.0.0.1','password' => 'pwd','database' => 0];
     */
    public function __construct(array $params)
    {
        $this->client = new \Predis\Client($params);
    }

    /**
     * 设置记录
     * @param $token string
     * @param array $arr ['access_token'=>'','client_id' => 'client1', 'expires'=>1, 'union_id' => 'union_id', 'user_id' => '1', 'scope'=>1];
     */
    public function setToken($token, array $arr)
    {
        $tokenKey = $this->getPrefix() . $token;
        $this->client->hmset($tokenKey, $arr);
        $extraKey = $this->buildExtraKey($token, $arr);
        $this->client->set($extraKey, $token);
        if (isset($arr['expires']) && intval($arr['expires']) > time()) {
            $this->client->expire($tokenKey, $arr['expires'] - time());
            $this->client->expire($extraKey, $arr['expires'] - time());
        }
    }

    /**
     * 通过键获取记录
     * @param $token
     * @return array
     */
    public function getToken($token)
    {
        $key = $this->getPrefix() . $token;
        return $this->client->hgetall($key);
    }

    /**
     * 通过键删除
     * @param $token
     */
    public function deleteToken($token)
    {
        $key = $this->getPrefix() . $token;
        $this->client->del([$key]);
    }

    /**
     * 删除与用户相关的所有记录
     * delete by user_id
     * @param $userId
     */
    public function deleteUserToken($userId)
    {
        $userIdKeys = $this->getKeysByUserId($userId);
        $this->delKeys($userIdKeys);
    }

    /**
     * 删除与clientId和userId相关的所有记录
     * @param $clientId
     * @param $userId
     */
    public function deleteUserClientToken($clientId, $userId)
    {
        $userIidClientIdKeys = $this->getKeysByClientIdAndUserId($clientId, $userId);
        $this->delKeys($userIidClientIdKeys);
    }

    /**
     * 删除与clientId有关的所有token
     * @param $clientId
     * @return void
     */
    public function deleteClientToken($clientId)
    {
        $clientIdKeys = $this->getKeysByClientId($clientId);
        $this->delKeys($clientIdKeys);
    }

    /**
     * 删除当前记录之外的所有记录
     * @param $currentToken
     * @param $clientId
     * @param $userId
     */
    public function deleteOldUserClientToken($currentToken, $clientId, $userId)
    {
        $clientIidIUserIdKeys = $this->getKeysByClientIdAndUserId($clientId, $userId);
        if (count($clientIidIUserIdKeys) > 0) {
            foreach ($clientIidIUserIdKeys as $key => $v) {
                $token = $this->client->get($v);
                if ($token !== $currentToken) {
                    $this->deleteToken($token);
                    $this->client->del($v);
                }
            }
        }
    }

    /**
     * 生成关联键值对
     * @param $token
     * @param array $arr ['access_token'=>'','client_id' => 'client1', 'expires'=>1, 'union_id' => 'union_id', 'user_id' => '1', 'scope'=>1];
     * @return string
     */
    private function buildExtraKey($token, array $arr)
    {
        $clientIdUserIdKey = "client_id" . "_" . "{$arr['client_id']}" . "-" . "user_id" . "_" . "{$arr['user_id']}" . "-" . "access_token" . "_" . $token . "-" . "union_id" . "_" . "{$arr['union_id']}";
        return $this->getPrefix() . $clientIdUserIdKey;
    }

    /**
     * 客户端id和用户id 模糊匹配出所有键
     * @param $clientId
     * @param $userId
     * @return array
     */
    private function getKeysByClientIdAndUserId($clientId, $userId)
    {
        $clientIdUserIdKeys = $this->getPrefix() . "client_id" . "_" . "{$clientId}" . "-" . "user_id" . "_" . "{$userId}" . "-*";
        return $this->client->keys($clientIdUserIdKeys);
    }

    /**
     * 通过clientId取出clientId对应的与主记录关联的键数组
     * @param $clientId
     * @return array
     */
    private function getKeysByClientId($clientId)
    {
        $clientIdKey = $this->getPrefix() . "client_id" . "_" . "{$clientId}" . "-*";
        return $this->client->keys($clientIdKey);
    }

    /**
     * 通过用户ID取出用户对应的与主记录关联的键数组
     * @param $userId
     * @return array
     */
    private function getKeysByUserId($userId)
    {
        $userIdKey = $this->getPrefix() . "*-user_id" . "_" . "{$userId}" . "-*";
        return $this->client->keys($userIdKey);
    }

    /**
     * 删除与key有关的所有记录
     * @param $keys
     */
    private function delKeys($keys)
    {
        if (count($keys) > 0) {
            foreach ($keys as $key => $v) {
                $token = $this->client->get($v);
                $this->deleteToken($token);
                $this->client->del($v);
            }
        }
    }

    /**
     * 所有记录的key值前缀
     * @return string
     */
    private function getPrefix()
    {
        return "";
    }
}
