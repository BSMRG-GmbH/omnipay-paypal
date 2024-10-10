<?php

namespace Omnipay\PaypalV2;

interface TokenStorageInterface {
    public function getAccessToken(): string;

    public function storeAccessToken(string $token); 

    public function getAccessTokenTTL(): int;

    public function storeAccessTokenTTL(int $ttl);
}