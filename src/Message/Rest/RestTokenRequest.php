<?php
/**
 * PayPal REST Token Request
 *
 * @link https://github.com/thephpleague/omnipay-paypal
 */

namespace Omnipay\PaypalV2\Message\Rest;

/**
 * PayPal REST Token Request
 *
 * With each API call, you’ll need to set request headers, including
 * an OAuth 2.0 access token. Get an access token by using the OAuth
 * 2.0 client_credentials token grant type with your clientId:secret
 * as your Basic Auth credentials.
 *
 * @link https://developer.paypal.com/docs/integration/direct/make-your-first-call/
 * @link https://developer.paypal.com/docs/api/#authentication--headers
 */
class RestTokenRequest extends AbstractRequest
{
    public function getData()
    {
        return [
            'grant_type' => 'client_credentials',
            'ignoreCache' => 'true',
        ];
    }

    protected function getEndpoint()
    {
        $base = $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;

        return $base.'v1/oauth2/token';
    }

    public function sendData($data)
    {
        $body = $data ? http_build_query($data) : null;

        $httpResponse = $this->httpClient->request(
            $this->getHttpMethod(),
            $this->getEndpoint(),
            [
                'Accept' => 'application/json',
                'Authorization' => 'Basic '.base64_encode("{$this->getClientId()}:{$this->getSecret()}"),
            ],
            $body
        );
        // Empty response body should be parsed also as and empty array
        $body = (string) $httpResponse->getBody()->getContents();
        $jsonToArrayResponse = ! empty($body) ? json_decode($body, true) : [];

        return $this->response = new RestResponse($this, $jsonToArrayResponse, $httpResponse->getStatusCode());
    }
}
