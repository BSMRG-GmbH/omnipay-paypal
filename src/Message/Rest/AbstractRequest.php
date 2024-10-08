<?php
/**
 * @author    王锶奇 <wangsiqi2@100tal.com>
 *
 * @time      2019/12/17 4:59 下午
 *
 * @copyright 2019 好未来教育科技集团-考满分事业部
 * @license   http://www.kmf.com license
 */

namespace Omnipay\PaypalV2\Message\Rest;

use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\ResponseInterface;

abstract class AbstractRequest extends \Omnipay\Common\Message\AbstractRequest
{
    const API_VERSION = 'v2';

    protected $liveEndpoint = 'https://api-m.paypal.com/';

    protected $testEndpoint = 'https://api-m.sandbox.paypal.com/';

    protected $referrerCode;

    /**
     * @return string
     */
    public function getReferrerCode()
    {
        return $this->referrerCode;
    }

    public function getClientId()
    {
        return $this->getParameter('clientId');
    }

    public function setClientId($value)
    {
        return $this->setParameter('clientId', $value);
    }

    public function getSecret()
    {
        return $this->getParameter('secret');
    }

    public function setSecret($value)
    {
        return $this->setParameter('secret', $value);
    }

    public function getToken()
    {
        return $this->getParameter('token');
    }

    public function setToken($value)
    {
        return $this->setParameter('token', $value);
    }

    public function setBrandName($value)
    {
        return $this->setParameter('brandName', $value);
    }

    protected function getBrandName()
    {
        return $this->getParameter('brandName');
    }

    /**
     * Get HTTP Method.
     *
     * This is nearly always POST but can be over-ridden in sub classes.
     *
     * @return string
     */
    protected function getHttpMethod()
    {
        return 'POST';
    }

    /**
     * 发起请求
     *
     * @param  mixed  $data
     * @return ResponseInterface
     *
     * @throws InvalidResponseException
     */
    public function sendData($data)
    {
        if ($this->getHttpMethod() == 'GET') {
            $body = null;
        } else {
            $body = json_encode($data);
        }
        try {
            $httpResponse = $this->httpClient->request(
                $this->getHttpMethod(),
                $this->getEndpoint(),
                [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$this->getToken(),
                    'Content-type' => 'application/json',
                    'PayPal-Partner-Attribution-Id' => $this->getReferrerCode(),
                    'prefer' => 'return=representation',
                ],
                $body
            );
            // Empty response body should be parsed also as and empty array
            $body = (string) $httpResponse->getBody()->getContents();
            $jsonToArrayResponse = ! empty($body) ? json_decode($body, true) : [];

            return $this->response = $this->createResponse($jsonToArrayResponse, $httpResponse->getStatusCode());
        } catch (\Exception $e) {
            throw new InvalidResponseException(
                'Error communicating with payment gateway: '.$e->getMessage(),
                $e->getCode()
            );
        }
    }

    protected function getEndpoint()
    {
        $base = $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;

        return $base.self::API_VERSION;
    }

    protected function createResponse($data, $statusCode)
    {
        return $this->response = new RestResponse($this, $data, $statusCode);
    }
}
