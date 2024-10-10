<?php

namespace Omnipay\PaypalV2;

class DefaultTokenStorageAdpater implements TokenStorageInterface {
    protected string $token = '';
    protected int $ttl = 0;

    public function getAccessToken(): string
    {
        return $this->token;
    }

    public function storeAccessToken(string $token)
    {
        $this->token = $token;
    }

    public function getAccessTokenTTL(): int
    {
        return $this->ttl;
    }

    public function storeAccessTokenTTL(int $ttl)
    {
        $this->ttl = $ttl;
    }
}