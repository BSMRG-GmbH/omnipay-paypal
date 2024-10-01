<?php
/**
 * @author    王锶奇 <wangsiqi2@100tal.com>
 *
 * @time      2019/12/17 4:48 下午
 *
 * @copyright 2019 好未来教育科技集团-考满分事业部
 * @license   http://www.kmf.com license
 */

namespace Omnipay\PaypalV2;

use Exception;
use Illuminate\Support\Facades\Cache;
use Omnipay\Common\AbstractGateway;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\PaypalV2\Message\Rest\AbstractRequest;
use Omnipay\PaypalV2\Message\Rest\CompletePurchaseRequest;
use Omnipay\PaypalV2\Message\Rest\FetchCaptureRequest;
use Omnipay\PaypalV2\Message\Rest\FetchTransactionListRequest;
use Omnipay\PaypalV2\Message\Rest\FetchTransactionRequest;
use Omnipay\PaypalV2\Message\Rest\PurchaseRequest;
use Omnipay\PaypalV2\Message\Rest\RefundRequest;
use Omnipay\PaypalV2\Message\Rest\RestTokenRequest;

/**
 * @method RequestInterface authorize(array $options = array ())
 * @method RequestInterface completeAuthorize(array $options = array ())
 * @method RequestInterface capture(array $options = array ())
 * @method RequestInterface void(array $options = array ())
 * @method RequestInterface createCard(array $options = array ())
 * @method RequestInterface updateCard(array $options = array ())
 * @method RequestInterface deleteCard(array $options = array ())
 */
class RestGateway extends AbstractGateway
{
    public const PAYPAL_REST_TOKEN_CACHE_KEY = 'PAYPAL_REST_TOKEN';

    public const PAYPAL_REST_TOKEN_EXPIRES_CACHE_KEY = 'PAYPAL_REST_TOKEN_EXPIRES';

    public function getDefaultParameters()
    {
        return [
            'clientId' => '',
            'secret' => '',
            'token' => Cache::get(self::PAYPAL_REST_TOKEN_CACHE_KEY, ''),
            'tokenExpires' => Cache::get(self::PAYPAL_REST_TOKEN_EXPIRES_CACHE_KEY, ''),
            'testMode' => false,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'PayPal REST API V2';
    }

    /**
     * Get OAuth 2.0 client ID for the access token.
     *
     * Get an access token by using the OAuth 2.0 client_credentials
     * token grant type with your clientId:secret as your Basic Auth
     * credentials.
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->getParameter('clientId');
    }

    /**
     * Set OAuth 2.0 client ID for the access token.
     *
     * Get an access token by using the OAuth 2.0 client_credentials
     * token grant type with your clientId:secret as your Basic Auth
     * credentials.
     *
     * @param  string  $value
     * @return RestGateway provides a fluent interface
     */
    public function setClientId($value)
    {
        return $this->setParameter('clientId', $value);
    }

    /**
     * Get OAuth 2.0 secret for the access token.
     *
     * Get an access token by using the OAuth 2.0 client_credentials
     * token grant type with your clientId:secret as your Basic Auth
     * credentials.
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->getParameter('secret');
    }

    /**
     * Set OAuth 2.0 secret for the access token.
     *
     * Get an access token by using the OAuth 2.0 client_credentials
     * token grant type with your clientId:secret as your Basic Auth
     * credentials.
     *
     * @param  string  $value
     * @return RestGateway provides a fluent interface
     */
    public function setSecret($value)
    {
        return $this->setParameter('secret', $value);
    }

    /**
     * Set Brand Name.
     *
     *
     * @param  string  $value
     * @return RestGateway provides a fluent interface
     */
    public function setBrandName($value)
    {
        return $this->setParameter('brandName', $value);
    }

    /**
     * Get OAuth 2.0 access token.
     *
     * @param  bool  $createIfNeeded [optional] - If there is not an active token present, should we create one?
     * @return string
     */
    public function getToken($createIfNeeded = true)
    {
        if ($createIfNeeded && ! $this->hasToken()) {
            $response = $this->createToken()->send();

            if ($response->isSuccessful()) {
                $data = $response->getData();

                if (isset($data['access_token'])) {
                    $this->setToken($data['access_token']);
                    $this->setTokenExpires(time() + $data['expires_in']);
                }
            }
        }

        return $this->getParameter('token');
    }

    /**
     * Create OAuth 2.0 access token request.
     */
    public function createToken(): RestTokenRequest
    {
        return $this->createRequest(RestTokenRequest::class, [
            'clientId' => $this->getClientId(),
            'secret' => $this->getSecret(),
        ]);
    }

    /**
     * Set OAuth 2.0 access token.
     *
     * @param  string  $value
     * @return RestGateway provides a fluent interface
     */
    public function setToken($value)
    {
        Cache::set(self::PAYPAL_REST_TOKEN_CACHE_KEY, $value);

        return $this->setParameter('token', $value);
    }

    /**
     * Get OAuth 2.0 access token expiry time.
     *
     * @return int
     */
    public function getTokenExpires()
    {
        return $this->getParameter('tokenExpires');
    }

    /**
     * Set OAuth 2.0 access token expiry time.
     *
     * @param  int  $value
     * @return RestGateway provides a fluent interface
     */
    public function setTokenExpires($value)
    {
        Cache::set(self::PAYPAL_REST_TOKEN_EXPIRES_CACHE_KEY, $value);

        return $this->setParameter('tokenExpires', $value);
    }

    /**
     * Is there a bearer token and is it still valid?
     *
     * @return bool
     */
    public function hasToken()
    {
        $token = $this->getParameter('token');

        $expires = $this->getTokenExpires();
        if (! empty($expires) && ! is_numeric($expires)) {
            $expires = strtotime($expires);
        }

        return ! empty($token) && time() < $expires;
    }

    /**
     * Create Request
     *
     * This overrides the parent createRequest function ensuring that the OAuth
     * 2.0 access token is passed along with the request data -- unless the
     * request is a RestTokenRequest in which case no token is needed.  If no
     * token is available then a new one is created (e.g. if there has been no
     * token request or the current token has expired).
     *
     * @param  string  $class
     * @param  array  $parameters
     */
    public function createRequest($class, array $parameters = []): AbstractRequest
    {
        if (! $this->hasToken() && $class != RestTokenRequest::class) {
            // This will set the internal token parameter which the parent
            // createRequest will find when it calls getParameters().
            $this->getToken(true);
        }

        return parent::createRequest($class, $parameters);
    }

    public function purchase(array $options): PurchaseRequest
    {
        return $this->createRequest(PurchaseRequest::class, $options);
    }

    public function completePurchase(array $options): CompletePurchaseRequest
    {
        return $this->createRequest(CompletePurchaseRequest::class, $options);
    }

    public function refund(array $options): RefundRequest
    {
        return $this->createRequest(RefundRequest::class, $options);
    }

    public function fetchTransaction(array $options): FetchTransactionRequest
    {
        return $this->createRequest(FetchTransactionRequest::class, $options);
    }

    public function fetchCapture(array $options): FetchCaptureRequest
    {
        return $this->createRequest(FetchCaptureRequest::class, $options);
    }

    public function fetchTransactionList(array $options): FetchTransactionListRequest
    {
        return $this->createRequest(FetchTransactionListRequest::class, $options);
    }

    public function acceptNotification(array $options)
    {
        throw new Exception('Unimplemented.');
    }
}
