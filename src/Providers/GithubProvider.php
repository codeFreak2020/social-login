<?php

namespace SocialLogin\Providers;

use SocialLogin\DTO\User;

class GithubProvider extends AbstractOAuth2Provider
{
    protected function authorizeUrl(): string
    {
        return 'https://github.com/login/oauth/authorize';
    }

    protected function tokenUrl(): string
    {
        return 'https://github.com/login/oauth/access_token';
    }

    protected function resourceOwnerUrl(): string
    {
        return 'https://api.github.com/user';
    }

    protected function defaultScopes(): array
    {
        return ['read:user', 'user:email'];
    }

    protected function doFetchUser(string $accessToken): User
    {
        $resp = $this->http->get('https://api.github.com/user', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/vnd.github+json',
            ],
        ]);
        $profile = json_decode((string) $resp->getBody(), true) ?: [];

        $email = $profile['email'] ?? null;
        if (!$email) {
            $emailsResp = $this->http->get('https://api.github.com/user/emails', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/vnd.github+json',
                ],
            ]);
            $emails = json_decode((string) $emailsResp->getBody(), true) ?: [];
            foreach ($emails as $e) {
                if (($e['primary'] ?? false) && ($e['verified'] ?? false)) {
                    $email = $e['email'] ?? null;
                    break;
                }
            }
            if (!$email && isset($emails[0]['email'])) {
                $email = $emails[0]['email'];
            }
        }

        $id = $profile['id'] ?? null;
        $name = $profile['name'] ?? ($profile['login'] ?? null);
        $avatar = $profile['avatar_url'] ?? null;

        return new User(
            id: (string) $id,
            name: $name,
            email: $email,
            avatar: $avatar,
            raw: $profile,
            provider: 'github'
        );
    }
}
