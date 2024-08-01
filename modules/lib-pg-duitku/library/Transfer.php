<?php
/**
 * Transfer
 * @package lib-pg-duitku
 * @version 0.4.0
 */

namespace LibPgDuitku\Library;

use LibCurl\Library\Curl;

class Transfer
{
    protected static $error;

    protected static function getConfig()
    {
        return \Mim::$app->config->libPgDuitku->transfer;
    }

    protected static function getHost()
    {
        $config = self::getConfig();
        if ($config->sandbox) {
            return 'https://sandbox.duitku.com';
        } else {
            return 'https://passport.duitku.com/';
        }
    }

    static function lastError()
    {
        return self::$error;
    }

    static function setError(string $error, string $code = null) {
        self::$error = 'PG: ' . $error;
        if ($code) {
            self::$error .= '(' . $code . ')';
        }
        return false;
    }

    static function check(array $data)
    {
        $config = self::getConfig();
        $host = self::getHost();
        $path = '/webapi/api/disbursement/inquirystatus';

        $time = round(microtime(true) * 1000);

        $body = [
            'disburseId' => $data['disburseId'],
            'userId' => $config->userId,
            'email' => $config->email,
            'timestamp' => $time
        ];

        $payload = implode('', [
            $body['email'],
            $time,
            $data['disburseId'],
            $config->secretKey
        ]);

        $body['signature'] = hash('sha256', $payload);

        $res = Curl::fetch([
            'url' => $host . $path,
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => $body
        ]);

        if (!$res) {
            return self::setError('Unable to reach DUITKU api');
        }

        if (is_string($res)) {
            return self::setError($res);
        }

        if (!isset($res->responseCode) && isset($res->responseDesc)) {
            return self::setError($res->responseDesc);
        }

        if ($res->responseCode != '00') {
            return self::setError($res->responseDesc);
        }

        return $res;
    }

    static function inquiry(array $data, string $type='ONLINE')
    {
        $config = self::getConfig();
        $host = self::getHost();
        $path = '/webapi/api/disbursement/inquiry';
        if ($type != 'ONLINE') {
            $path .= 'clearing';
        }
        if ($config->sandbox) {
            $path .= 'sandbox';
        }

        $time = round(microtime(true) * 1000);

        $body = [
            'userId' => $config->userId,
            'email' => $config->email,
            'bankCode' => $data['bank']['code'],
            'bankAccount' => $data['bank']['account']['number'],
            'amountTransfer' => $data['resume']['amount'],
            'senderName' => $data['user']['name'],
            'senderId' => $data['user']['id'],
            'purpose' => $data['info'],
            'type' => $type,
            'timestamp' => $time
        ];
        if (isset($data['user']['reff'])) {
            $body['custRefNumber'] = $data['user']['reff'];
        }

        $payload = implode('', [
            $body['email'],
            $time,
            $body['bankCode'],
            $body['bankAccount'],
            $body['amountTransfer'],
            $body['purpose'],
            $config->secretKey
        ]);

        if ($type != 'ONLINE') {
            $payload = implode('', [
                $body['email'],
                $time,
                $body['bankCode'],
                $type,
                $body['bankAccount'],
                $body['amountTransfer'],
                $body['purpose'],
                $config->secretKey
            ]);
        }

        $body['signature'] = hash('sha256', $payload);

        $res = Curl::fetch([
            'url' => $host . $path,
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => $body
        ]);

        if (!$res) {
            return self::setError('Unable to reach DUITKU api');
        }

        if (is_string($res)) {
            return self::setError($res);
        }

        if (!isset($res->responseCode) && isset($res->responseDesc)) {
            return self::setError($res->responseDesc);
        }

        if ($res->responseCode != '00') {
            return self::setError($res->responseDesc);
        }

        return $res;
    }

    static function bifast(array $data)
    {
        return self::send($data, 'BIFAST');
    }

    static function online(array $data)
    {
        return self::send($data, 'ONLINE');
    }

    static function send(array $data, string $type)
    {
        $result = self::inquiry($data, $type);
        if (!$result) {
            return false;
        }

        $config = self::getConfig();

        $dist_id = $result->disburseId;
        $cust_no = $result->custRefNumber;
        $accn_nm = $result->accountName;

        $host = self::getHost();
        $path = '/webapi/api/disbursement/transfer';
        if ($type != 'ONLINE') {
            $path .= 'clearing';
        }
        if ($config->sandbox) {
            $path .= 'sandbox';
        }

        $time = round(microtime(true) * 1000);

        $body = [
            'disburseId' => $dist_id,
            'userId' => $config->userId,
            'email' => $config->email,
            'bankCode' => $data['bank']['code'],
            'bankAccount' => $data['bank']['account']['number'],
            'amountTransfer' => $data['resume']['amount'],
            'accountName' => $accn_nm,
            'custRefNumber' => $cust_no,
            'purpose' => $data['info'],
            'type' => $type,
            'timestamp' => $time
        ];

        $payload = implode('', [
            $body['email'],
            $time,
            $body['bankCode'],
            $body['bankAccount'],
            $accn_nm,
            $cust_no,
            $body['amountTransfer'],
            $body['purpose'],
            $dist_id,
            $config->secretKey
        ]);
        if ($type != 'ONLINE') {
            $payload = implode('', [
                $body['email'],
                $time,
                $body['bankCode'],
                $type,
                $body['bankAccount'],
                $accn_nm,
                $cust_no,
                $body['amountTransfer'],
                $body['purpose'],
                $dist_id,
                $config->secretKey
            ]);
        }

        $body['signature'] = hash('sha256', $payload);

        $res = Curl::fetch([
            'url' => $host . $path,
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => $body
        ]);

        if (!$res) {
            return self::setError('Unable to reach DUITKU api');
        }

        if (is_string($res)) {
            return self::setError($res);
        }

        if (!isset($res->responseCode) && isset($res->responseDesc)) {
            return self::setError($res->responseDesc);
        }

        if ($res->responseCode != '00') {
            return self::setError($res->responseDesc);
        }

        $res->disburseId = $dist_id;

        return $res;
    }

    static function getBanks()
    {
        $config = self::getConfig();
        $host = self::getHost();
        $path = '/webapi/api/disbursement/listBank';

        $time = round(microtime(true) * 1000);

        $body = [
            'userId' => $config->userId,
            'email' => $config->email,
            'timestamp' => $time
        ];

        $payload = implode('', [
            $body['email'],
            $time,
            $config->secretKey
        ]);

        $body['signature'] = hash('sha256', $payload);

        $res = Curl::fetch([
            'url' => $host . $path,
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => $body
        ]);

        if (!$res) {
            return self::setError('Unable to reach DUITKU api');
        }

        if (is_string($res)) {
            return self::setError($res);
        }

        if (!isset($res->responseCode) && isset($res->responseDesc)) {
            return self::setError($res->responseDesc);
        }

        if ($res->responseCode != '00') {
            return self::setError($res->responseDesc);
        }

        return $res;
    }
}
