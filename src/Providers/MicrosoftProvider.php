<?php

namespace SocialLogin\Providers;

use SocialLogin\DTO\User;

/**
 * Microsoft (Azure AD / Microsoft Account) OAuth2 Provider
 * 
 * Required scopes: openid, profile, email, User.Read
 * 
 * @see https://learn.microsoft.com/en-us/azure/active-directory/develop/v2-oauth2-auth-code-flow
 */
class MicrosoftProvider extends AbstractOAuth2Provider
{
    protected function authorizeUrl(): string
    {
        // Using common tenant for both personal and work/school accounts
        // You can also use 'consumers', 'organizations', or a specific tenant ID
        return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';
    }

    protected function tokenUrl(): string
    {
        return 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
    }

    protected function resourceOwnerUrl(): string
    {
        return 'https://graph.microsoft.com/v1.0/me';
    }

    protected function defaultScopes(): array
    {
        return ['openid', 'profile', 'email', 'User.Read'];
    }

    protected function doFetchUser(string $accessToken): User
    {
        // Fetch user profile from Microsoft Graph
        $resp = $this->http->get('https://graph.microsoft.com/v1.0/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);

        $data = json_decode((string) $resp->getBody(), true) ?: [];
        
        $id = $data['id'] ?? null;
        $name = $data['displayName'] ?? trim(($data['givenName'] ?? '') . ' ' . ($data['surname'] ?? '')) ?: null;
        $email = $data['mail'] ?? $data['userPrincipalName'] ?? null;
        
        // Avatar requires separate call to /photo/$value endpoint
        $avatar = null;

        return new User(
            id: (string) $id,
            name: $name,
            email: $email,
            avatar: $avatar,
            raw: $data,
            provider: 'microsoft'
        );
    }
}
