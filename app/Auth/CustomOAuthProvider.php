<?php

namespace App\Auth;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class CustomOAuthProvider extends AbstractProvider implements ProviderInterface
{
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(config('services.custom_oauth.server_url') . '/oauth/authorize', $state);
    }

    protected function getTokenUrl()
    {
        return config('services.custom_oauth.server_url') . '/oauth/token';
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(config('services.custom_oauth.server_url') . '/api/user', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'] ?? null,
            'email'    => $user['email'] ?? null,
            'name'     => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
            'first_name' => $user['first_name'] ?? null,
            'last_name'  => $user['last_name'] ?? null,
            'phone'    => $user['phone'] ?? null,
        ]);
    }
}
