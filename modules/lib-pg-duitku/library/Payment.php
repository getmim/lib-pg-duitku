<?php
/**
 * Payment
 * @package lib-pg-duitku
 * @version 0.4.0
 */

namespace LibPgDuitku\Library;

use LibCurl\Library\Curl;

class Payment
{
    protected static $error;

    protected static function getConfig()
    {
        return \Mim::$app->config->libPgDuitku->payment;
    }

    static function lastError()
    {
        return self::$error;
    }

    /**
     * @data [
     *  invoice::string,
     *  total::float
     * ]
     */
    static function createBill($data)
    {
        $config = self::getConfig();
        $time = date('Y-m-d H:i:s');

        $payload = implode('', [
            $config->merchantCode,
            $data['invoice'],
            $data['total'],
            $config->apiKey
        ]);
        $signature = md5($payload);

        $body = [
            'merchantCode'      => $config->merchantCode,
            'paymentAmount'     => $data['total'],
            'paymentMethod'     => $data['method'],
            'merchantOrderId'   => $data['invoice'],
            'productDetails'    => $data['info'],
            'additionalParam'   => $data['additional'] ?? '',
            'merchantUserInfo'  => $data['username'] ?? '',
            'customerVaName'    => $data['customer']['name'],
            'email'             => $data['customer']['email'],
            'phoneNumber'       => $data['customer']['phone'],
            'accountLink'       => $data['customer']['link'] ?? null,
            'creditCardDetail'  => $data['customer']['card'] ?? null,
            'itemDetails'       => $data['items'],
            'customerDetail'    => $data['customer'],
            'callbackUrl'       => $data['callback'],
            'returnUrl'         => $data['return'],
            'signature'         => $signature,
            'expiryPeriod'      => $data['expires'],
        ];

        $res = Curl::fetch([
            'url' => $config->host . '/webapi/api/merchant/v2/inquiry',
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

        if (!isset($res->statusCode) && isset($res->Message)) {
            return self::setError($res->Message);
        }

        if ($res->statusCode != '00') {
            deb($res);
        }

        return $res;
    }

    static function getInstruction($code)
    {
        $path_by_code = [
            // Virtual Account
            // MAYBANK
            'VA' => '/TopUp/v2/PaymentInsVa/Maybank.html',
            // PERMATA
            'BT' => '/TopUp/v2/PaymentInsVa/Permata.html',
            // CIMB NIAGA
            'B1' => '/TopUp/v2/PaymentInsVa/CIMB.html',
            // ATM BERSAMA
            'A1' => '/TopUp/v2/PaymentInsVa/Maybank.html',
            // BNI
            'I1' => '/TopUp/v2/PaymentInsVa/BNI.html',
            // MANDIRI
            'M1' => '/TopUp/v2/PaymentInsVa/Mandiri.html',
            // ARTA GRAHA
            'AG' => '/TopUp/v2/PaymentInsVa/ATMBersama.html',
            // BCA
            'BC' => '/TopUp/v2/PaymentInsVa/BCA.html',
            // BRI
            'BR' => '/TopUp/v2/PaymentInsVa/BRIVA.html',
            // BNC
            'NC' => '/TopUp/v2/PaymentInsVa/BNC.html'
        ];

        if (!isset($path_by_code[$code])) {
            return [];
        }

        $config = self::getConfig();
        $url = chop($config->host, '/') . $path_by_code[$code];
        $res = Curl::fetch([
            'url' => $url
        ]);

        $result = [];

        $dom = new \DomDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($res);

        $h5s = $dom->getElementsByTagName('h5');
        foreach ($h5s as $h5) {
            $group = trim($h5->textContent);
            $result[$group] = [];

            $parent = $h5->parentNode->parentNode;
            $ols = $parent->getElementsByTagName('ol');
            $lis = $ols[0]->getElementsByTagName('li');
            foreach ($lis as $li) {
                $result[$group][] = trim($li->textContent);
            }
        }

        return $result;
    }

    static function getPaymentMethods($amount)
    {
        $config = self::getConfig();
        $time = date('Y-m-d H:i:s');

        $payload = implode('', [
            $config->merchantCode,
            $amount,
            $time,
            $config->apiKey
        ]);
        $signature = hash('sha256', $payload);

        $body = [
            'merchantcode' => $config->merchantCode,
            'amount' => $amount,
            'datetime' => $time,
            'signature' => $signature
        ];

        $host = $config->host;
        $url = $host . '/webapi/api/merchant/paymentmethod/getpaymentmethod';
        $res = Curl::fetch([
            'url' => $url,
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

        if (!isset($res->responseCode) && isset($res->Message)) {
            return self::setError($res->Message);
        }

        if ($res->responseCode != '00') {
            deb($res);
        }

        $result = [];
        $groupsByCode = [
            'QRIS'            => ['SP','LQ','NQ','DQ','GQ','SQ'],
            'Virtual Account' => ['BC','M2','VA','I1','B1','BT','A1','AG','NC','BR','M1'],
            'Retail'          => ['FT','IR'],
            'Kartu Kredit'    => ['VC'],
            'Paylater'        => ['DN','AT'],
            'E-Wallet'        => ['OV','SA','LF','LA','DA','SL','OL'],
            'E-Banking'       => ['JP']
        ];
        foreach ($res->paymentFee as $method) {
            $pm = [
                'code' => $method->paymentMethod,
                'name' => $method->paymentName,
                'logo' => $method->paymentImage,
                'fee'  => $method->totalFee
            ];

            $group = null;

            foreach ($groupsByCode as $grp => $codes) {
                if (in_array($pm['code'], $codes)) {
                    $group = $grp;
                    break;
                }
            }

            if (!$group) {
                $group = 'Other';
            }

            if (!isset($result[$group])) {
                $result[$group] = [];
            }

            $result[$group][] = $pm;
        }

        return $result;
    }

    static function setError(string $error, string $code = null) {
        self::$error = 'PG: ' . $error;
        if ($code) {
            self::$error .= '(' . $code . ')';
        }
        return false;
    }

    static function validateCallback(array $data)
    {
        $fields = [
            'merchantCode',
            'amount',
            'merchantOrderId',
            'resultCode',
            'signature',
            'reference'
        ];

        foreach ($fields as $field) {
            if (!isset($data[$field])) {
                return self::setError('Field `' . $field . '` is required', 400);
            }
        }

        $config = self::getConfig();

        if ($data['merchantCode'] != $config->merchantCode) {
            return self::setError('Wrong `merchantCode`', 400);
        }

        $payload = implode('', [
            $config->merchantCode,
            $data['amount'],
            $data['merchantOrderId'],
            $config->apiKey
        ]);

        $signature = md5($payload);

        if ($signature != $data['signature']) {
            return self::setError('Bad `signature`', 400);
        }

        return $data;
    }
}
