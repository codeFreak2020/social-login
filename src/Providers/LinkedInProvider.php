<?php

namespace SocialLogin\Providers;

use SocialLogin\DTO\User;

/**
 * LinkedIn OAuth2 Provider
 * 
 * Required scopes: openid, profile, email
 * 
 * @see https://learn.microsoft.com/en-us/linkedin/shared/authentication/authentication
 */
class LinkedInProvider extends AbstractOAuth2Provider
{
    protected function authorizeUrl(): string
    {
        return 'https://www.linkedin.com/oauth/v2/authorization';
    }

    protected function tokenUrl(): string
    {
        return 'https://www.linkedin.com/oauth/v2/accessToken';
    }

    protected function resourceOwnerUrl(): string
    {
        return 'https://api.linkedin.com/v2/userinfo';
    }

    protected function defaultScopes(): array
    {
        return ['openid', 'profile', 'email'];
    }

    protected function doFetchUser(string $accessToken): User
    {
        // LinkedIn v2 userinfo endpoint
        $resp = $this->http->get('https://api.linkedin.com/v2/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);

        $data = json_decode((string) $resp->getBody(), true) ?: [];
        
        $id = $data['sub'] ?? null;
        $name = $data['name'] ?? trim(($data['given_name'] ?? '') . ' ' . ($data['family_name'] ?? '')) ?: null;
        $email = $data['email'] ?? null;
        $avatar = $data['picture'] ?? null;

        return new User(
            id: (string) $id,
            name: $name,
            email: $email,
            avatar: $avatar,
            raw: $data,
            provider: 'linkedin'
        );
    }
}
