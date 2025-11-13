<?php

namespace SocialLogin\Providers;

use SocialLogin\DTO\User;

/**
 * Generic OAuth2 Provider
 * 
 * Use this provider for ANY OAuth2 server, including your own custom authentication service.
 * All endpoints and configuration are passed via config, making it completely flexible.
 * 
 * Example for your custom OAuth2 service:
 * 
 * ```php
 * $config = [
 *     'providers' => [
 *         'my-auth-service' => [
 *             'driver' => GenericOAuth2Provider::class,
 *             'client_id' => 'your_client_id',
 *             'client_secret' => 'your_client_secret',
 *             'redirect_uri' => 'https://your-app.com/auth/callback',
 *             
 *             // Your OAuth2 server endpoints
 *             'authorize_url' => 'https://auth.yourcompany.com/oauth/authorize',
 *             'token_url' => 'https://auth.yourcompany.com/oauth/token',
 *             'userinfo_url' => 'https://auth.yourcompany.com/oauth/userinfo',
 *             
 *             // Optional customization
 *             'scopes' => ['openid', 'profile', 'email'],
 *             'scope_separator' => ' ',
 *             'pkce_method' => 'S256', // or 'plain', or null
 *             
 *             // User data mapping (optional)
 *             'user_id_field' => 'sub',        // default: 'id'
 *             'user_name_field' => 'name',     // default: 'name'
 *             'user_email_field' => 'email',   // default: 'email'
 *             'user_avatar_field' => 'picture', // default: 'avatar'
 *         ],
 *     ],
 * ];
 * ```
 * 
 * @see https://datatracker.ietf.org/doc/html/rfc6749 OAuth2 Spec
 */
class GenericOAuth2Provider extends AbstractOAuth2Provider
{
    protected function authorizeUrl(): string
    {
        return $this->config['authorize_url'] 
            ?? throw new \InvalidArgumentException('Missing required config: authorize_url');
    }

    protected function tokenUrl(): string
    {
        return $this->config['token_url'] 
            ?? throw new \InvalidArgumentException('Missing required config: token_url');
    }

    protected function resourceOwnerUrl(): string
    {
        return $this->config['userinfo_url'] ?? '';
    }

    protected function defaultScopes(): array
    {
        return $this->config['scopes'] ?? [];
    }

    protected function scopeSeparator(): string
    {
        return $this->config['scope_separator'] ?? ' ';
    }

    protected function doFetchUser(string $accessToken): User
    {
        $userinfoUrl = $this->resourceOwnerUrl();
        
        if (empty($userinfoUrl)) {
            throw new \RuntimeException(
                'Missing userinfo_url in config. GenericOAuth2Provider requires userinfo_url to fetch user data.'
            );
        }

        // Fetch user profile from your OAuth2 server
        $resp = $this->http->get($userinfoUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);

        $data = json_decode((string) $resp->getBody(), true) ?: [];

        // Map fields from your OAuth2 server response
        // Allow customization via config
        $idField = $this->config['user_id_field'] ?? 'id';
        $nameField = $this->config['user_name_field'] ?? 'name';
        $emailField = $this->config['user_email_field'] ?? 'email';
        $avatarField = $this->config['user_avatar_field'] ?? 'avatar';

        $id = $data[$idField] ?? ($data['sub'] ?? null);
        $name = $data[$nameField] ?? null;
        $email = $data[$emailField] ?? null;
        $avatar = $data[$avatarField] ?? ($data['picture'] ?? null);

        // Provider name from config or fallback
        $providerName = $this->config['provider_name'] ?? 'generic';

        return new User(
            id: (string) $id,
            name: $name,
            email: $email,
            avatar: $avatar,
            raw: $data,
            provider: $providerName
        );
    }
}
