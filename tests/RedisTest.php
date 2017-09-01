<?php
/**
 * Created by PhpStorm.
 * User: zjw
 * Date: 2017/8/30
 * Time: 上午10:14
 */

namespace monsterhunter\oauth2\redis\tests;

use monsterhunter\oauth2\redis\RedisClient;

class RedisTest extends TestCase
{
    /**
     * @var RedisClient
     */
    private $redisClient;

    public function setUp()
    {
        parent::setUp();

        /**
         * @var $client \Predis\Client
         */
        $params =
            [
                'host' => '127.0.0.1',
                 'password' => null,
                'database' => 0
            ];
        $this->redisClient = new RedisClient($params);
        $this->redisClient->client->flushall();
        $this->generateTestData();
    }

    public function generateTestData()
    {
        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $expires = time() + 5;
            $value = ['access_token' => $token, 'client_id' => 'client_id', 'expires' => $expires, 'union_id' => 'union_id1', 'user_id' => 'user_id', 'scope' => '1'];
            $this->redisClient->setToken($token, $value);
            $value = ['access_token' => $token, 'client_id' => 'client_id', 'expires' => $expires, 'union_id' => 'union_id1', 'user_id' => 'user_id1', 'scope' => '1'];
            $this->redisClient->setToken($token . $token, $value);
        }
    }

    public function testDeleteUserToken()
    {
        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $this->assertFalse($this->redisClient->getToken($token) == []);
        }
        $this->redisClient->deleteUserToken('user_id');
        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $this->assertTrue($this->redisClient->getToken($token) == []);
        }

        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $token = $token . $token;
            $this->assertFalse($this->redisClient->getToken($token) == []);
        }
        $this->redisClient->deleteUserToken('user_id1');
        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $token = $token . $token;
            $this->assertTrue($this->redisClient->getToken($token) == []);
        }
    }

    public function testDeleteByClientId()
    {
        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $this->assertFalse($this->redisClient->getToken($token) == []);
            $token = $token . $token;
            $this->assertFalse($this->redisClient->getToken($token) == []);
        }

        $this->redisClient->deleteClientToken('client_id');

        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $this->assertTrue($this->redisClient->getToken($token) == []);
            $token = $token . $token;
            $this->assertTrue($this->redisClient->getToken($token) == []);
        }
    }

    public function testByUserIdAndClientId()
    {
        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $this->assertFalse($this->redisClient->getToken($token) == []);
            $token = $token . $token;
            $this->assertFalse($this->redisClient->getToken($token) == []);
        }

        $this->redisClient->deleteUserClientToken('client_id', 'user_id');

        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $this->assertTrue($this->redisClient->getToken($token) == []);
            $token = $token . $token;
            $this->assertFalse($this->redisClient->getToken($token) == []);
        }

        $this->redisClient->deleteUserClientToken('client_id', 'user_id1');

        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $token = $token . $token;
            $this->assertTrue($this->redisClient->getToken($token) == []);
        }
    }

    public function testDeleteToken()
    {
        $token = time();
        $expires = $token + 5 * 60;
        $value = ['access_token' => $token, 'client_id' => 'client_id', 'expires' => $expires, 'union_id' => 'union_id1', 'user_id' => 'user_id', 'scope' => '1'];
        $this->redisClient->getToken($token, $value);
        $this->redisClient->deleteToken($token);
        $this->assertTrue($this->redisClient->getToken($token) == []);
    }

    public function testRevokeOldToken()
    {
        $token = time();
        $expires = $token + 5 * 60;
        $value = ['access_token' => $token, 'client_id' => 'client_id', 'expires' => $expires, 'union_id' => 'union_id1', 'user_id' => 'user_id', 'scope' => '1'];
        $this->redisClient->getToken($token, $value);
        $this->redisClient->deleteOldUserClientToken($token, 'client_id', 'user_id');

        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $this->assertTrue($this->redisClient->getToken($token) == []);
            $token = $token . $token;
            $this->assertFalse($this->redisClient->getToken($token) == []);
        }
    }

    public function testExpire()
    {
        sleep(5);
        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $this->assertTrue($this->redisClient->getToken($token) == []);
            $token = $token . $token;
            $this->assertTrue($this->redisClient->getToken($token) == []);
        }
    }
}
