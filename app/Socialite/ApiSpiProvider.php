<?php

namespace App\Socialite;

use GuzzleHttp\RequestOptions;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

/**
 * "Sign in with ApiSpi" — apispi.com's OpenID Connect provider.
 *
 * apispi.com requires PKCE (S256) on every authorization and accepts the
 * client secret in the token request body (client_secret_post), which is
 * what Socialite sends. Endpoints are those published at
 * {base}/.well-known/openid-configuration.
 */
class ApiSpiProvider extends AbstractProvider
{
    protected $scopes = ['openid', 'profile', 'email'];

    protected $scopeSeparator = ' ';

    protected $usesPKCE = true;

    protected function baseUrl(): string
    {
        return rtrim((string) config('services.apispi.base_url', 'https://apispi.com'), '/');
    }

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->baseUrl().'/oauth/authorize', $state);
    }

    protected function getTokenUrl()
    {
        return $this->baseUrl().'/api/oauth/token';
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->baseUrl().'/api/oauth/userinfo', [
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['sub'] ?? null,
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
            'avatar' => $user['picture'] ?? null,
        ]);
    }
}
