<?php
/**
 * Created by PhpStorm.
 * User: zjw
 * Date: 2017/8/30
 * Time: 上午10:14
 */

namespace monsterhunter\redis\relational\tests;

use monsterhunter\redis\relational\RedisClient;

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
        $client = new \Predis\Client(
            [
                'host' => '127.0.0.1',
                'password' => null,
                'database' => 0
            ]
        );
        $this->redisClient = new RedisClient($client);
        $this->redisClient->client->flushall();
        $this->generateTestData();
    }

    public function generateTestData()
    {
        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $expires = time() + 5;
            $value = ['access_token' => $token, 'client_id' => 'client_id', 'expires' => $expires, 'union_id' => 'union_id1', 'user_id' => 'user_id', 'scope' => '1'];
            $this->redisClient->hset($token, $value);
            $value = ['access_token' => $token, 'client_id' => 'client_id', 'expires' => $expires, 'union_id' => 'union_id1', 'user_id' => 'user_id1', 'scope' => '1'];
            $this->redisClient->hset($token . $token, $value);
        }
    }

    public function testDeleteByUserId()
    {
        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $this->assertFalse($this->redisClient->hget($token) == []);
        }
        $this->redisClient->deleteByUserId('user_id');
        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $this->assertTrue($this->redisClient->hget($token) == []);
        }

        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $token = $token . $token;
            $this->assertFalse($this->redisClient->hget($token) == []);
        }
        $this->redisClient->deleteByUserId('user_id1');
        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $token = $token . $token;
            $this->assertTrue($this->redisClient->hget($token) == []);
        }
    }

    public function testDeleteByClientId()
    {
        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $this->assertFalse($this->redisClient->hget($token) == []);
            $token = $token . $token;
            $this->assertFalse($this->redisClient->hget($token) == []);
        }

        $this->redisClient->deleteByClientId('client_id');

        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $this->assertTrue($this->redisClient->hget($token) == []);
            $token = $token . $token;
            $this->assertTrue($this->redisClient->hget($token) == []);
        }
    }

    public function testByUserIdAndClientId()
    {
        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $this->assertFalse($this->redisClient->hget($token) == []);
            $token = $token . $token;
            $this->assertFalse($this->redisClient->hget($token) == []);
        }

        $this->redisClient->deleteByUserIdAndClientId('client_id', 'user_id');

        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $this->assertTrue($this->redisClient->hget($token) == []);
            $token = $token . $token;
            $this->assertFalse($this->redisClient->hget($token) == []);
        }

        $this->redisClient->deleteByUserIdAndClientId('client_id', 'user_id1');

        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $token = $token . $token;
            $this->assertTrue($this->redisClient->hget($token) == []);
        }
    }

    public function testDeleteByToken()
    {
        $token = time();
        $expires = $token + 5 * 60;
        $value = ['access_token' => $token, 'client_id' => 'client_id', 'expires' => $expires, 'union_id' => 'union_id1', 'user_id' => 'user_id', 'scope' => '1'];
        $this->redisClient->hset($token, $value);
        $this->redisClient->deleteByToken($token);
        $this->assertTrue($this->redisClient->hget($token) == []);
    }

    public function testRevokeOldToken()
    {
        $token = time();
        $expires = $token + 5 * 60;
        $value = ['access_token' => $token, 'client_id' => 'client_id', 'expires' => $expires, 'union_id' => 'union_id1', 'user_id' => 'user_id', 'scope' => '1'];
        $this->redisClient->hset($token, $value);
        $this->redisClient->revokeOldToken($token, 'client_id', 'user_id');

        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $this->assertTrue($this->redisClient->hget($token) == []);
            $token = $token . $token;
            $this->assertFalse($this->redisClient->hget($token) == []);
        }
    }

    public function testExpire()
    {
        sleep(5);
        for ($i = 0; $i < 10; $i++) {
            $token = 'token' . $i;
            $this->assertTrue($this->redisClient->hget($token) == []);
            $token = $token . $token;
            $this->assertTrue($this->redisClient->hget($token) == []);
        }
    }
}
