<?php

namespace SocialLogin\Providers;

use SocialLogin\DTO\User;

class GoogleProvider extends AbstractOAuth2Provider
{
    protected function authorizeUrl(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth';
    }

    protected function tokenUrl(): string
    {
        return 'https://oauth2.googleapis.com/token';
    }

    protected function resourceOwnerUrl(): string
    {
        return 'https://www.googleapis.com/oauth2/v3/userinfo';
    }

    protected function defaultScopes(): array
    {
        return ['openid', 'email', 'profile'];
    }

    protected function doFetchUser(string $accessToken): User
    {
        $resp = $this->http->get('https://www.googleapis.com/oauth2/v3/userinfo', [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken],
        ]);
        $data = json_decode((string) $resp->getBody(), true) ?: [];
        $id = $data['sub'] ?? ($data['id'] ?? null);
        $name = $data['name'] ?? trim(($data['given_name'] ?? '') . ' ' . ($data['family_name'] ?? '')) ?: null;
        $email = $data['email'] ?? null;
        $avatar = $data['picture'] ?? null;

        return new User(
            id: (string) $id,
            name: $name,
            email: $email,
            avatar: $avatar,
            raw: $data,
            provider: 'google'
        );
    }
}
