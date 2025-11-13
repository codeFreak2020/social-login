<?php

namespace SocialLogin\Providers;

use SocialLogin\DTO\User;

/**
 * Facebook OAuth2 Provider
 * 
 * Required scopes: email, public_profile
 * 
 * @see https://developers.facebook.com/docs/facebook-login
 */
class FacebookProvider extends AbstractOAuth2Provider
{
    protected function authorizeUrl(): string
    {
        return 'https://www.facebook.com/v18.0/dialog/oauth';
    }

    protected function tokenUrl(): string
    {
        return 'https://graph.facebook.com/v18.0/oauth/access_token';
    }

    protected function resourceOwnerUrl(): string
    {
        return 'https://graph.facebook.com/v18.0/me';
    }

    protected function defaultScopes(): array
    {
        return ['email', 'public_profile'];
    }

    protected function scopeSeparator(): string
    {
        return ',';
    }

    protected function doFetchUser(string $accessToken): User
    {
        // Request user fields
        $resp = $this->http->get('https://graph.facebook.com/v18.0/me', [
            'query' => [
                'fields' => 'id,name,email,picture.type(large)',
                'access_token' => $accessToken,
            ],
        ]);

        $data = json_decode((string) $resp->getBody(), true) ?: [];
        
        $id = $data['id'] ?? null;
        $name = $data['name'] ?? null;
        $email = $data['email'] ?? null;
        $avatar = $data['picture']['data']['url'] ?? null;

        return new User(
            id: (string) $id,
            name: $name,
            email: $email,
            avatar: $avatar,
            raw: $data,
            provider: 'facebook'
        );
    }
}
