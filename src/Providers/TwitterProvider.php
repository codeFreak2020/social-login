<?php

namespace SocialLogin\Providers;

use League\OAuth2\Client\Provider\GenericProvider;
use SocialLogin\DTO\User;

/**
 * Twitter/X OAuth2 Provider
 * 
 * Note: Uses OAuth 2.0 with PKCE (Twitter API v2)
 * Required scopes: tweet.read, users.read
 * 
 * @see https://developer.twitter.com/en/docs/authentication/oauth-2-0/authorization-code
 */
class TwitterProvider extends AbstractOAuth2Provider
{
    public function __construct(array $config)
    {
        // Twitter requires PKCE for OAuth 2.0
        $config['pkce_method'] = $config['pkce_method'] ?? GenericProvider::PKCE_METHOD_S256;
        parent::__construct($config);
    }

    protected function authorizeUrl(): string
    {
        return 'https://twitter.com/i/oauth2/authorize';
    }

    protected function tokenUrl(): string
    {
        return 'https://api.twitter.com/2/oauth2/token';
    }

    protected function resourceOwnerUrl(): string
    {
        return 'https://api.twitter.com/2/users/me';
    }

    protected function defaultScopes(): array
    {
        return ['tweet.read', 'users.read'];
    }

    protected function doFetchUser(string $accessToken): User
    {
        // Fetch user profile with expanded fields
        $resp = $this->http->get('https://api.twitter.com/2/users/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
            'query' => [
                'user.fields' => 'id,name,username,profile_image_url',
            ],
        ]);

        $response = json_decode((string) $resp->getBody(), true) ?: [];
        $data = $response['data'] ?? [];
        
        $id = $data['id'] ?? null;
        $name = $data['name'] ?? null;
        $username = $data['username'] ?? null;
        $avatar = $data['profile_image_url'] ?? null;

        // Twitter API v2 doesn't provide email in basic user endpoint
        // You need to request email separately with tweet.read users.read email scopes
        $email = null;

        return new User(
            id: (string) $id,
            name: $name ?: $username,
            email: $email,
            avatar: $avatar,
            raw: $data,
            provider: 'twitter'
        );
    }
}
