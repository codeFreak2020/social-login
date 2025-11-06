<?php

namespace SocialLogin\Providers;

use GuzzleHttp\Client;
use League\OAuth2\Client\Provider\GenericProvider;
use SocialLogin\Contracts\ProviderDriver;
use SocialLogin\DTO\User;
use SocialLogin\Exceptions\SocialLoginException;
use Throwable;

abstract class AbstractOAuth2Provider implements ProviderDriver
{
    protected GenericProvider $oauth;
    protected Client $http;
    protected ?string $lastState = null;

    public function __construct(protected array $config)
    {
        $this->oauth = new GenericProvider([
            'clientId' => $config['client_id'] ?? null,
            'clientSecret' => $config['client_secret'] ?? null,
            'redirectUri' => $config['redirect_uri'] ?? null,
            'urlAuthorize' => $this->authorizeUrl(),
            'urlAccessToken' => $this->tokenUrl(),
            'urlResourceOwnerDetails' => $this->resourceOwnerUrl(),
            // Default scopes and options
            'scopes' => $config['scopes'] ?? $this->defaultScopes(),
            'scopeSeparator' => $config['scope_separator'] ?? $this->scopeSeparator(),
            'pkceMethod' => $config['pkce_method'] ?? null,
        ]);

        $this->http = new Client([
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'acme-social-login/1.0',
            ],
        ]);
    }

    public static function fromConfig(array $config): ProviderDriver
    {
        return new static($config);
    }

    abstract protected function authorizeUrl(): string;
    abstract protected function tokenUrl(): string;
    protected function resourceOwnerUrl(): string
    {
        return '';
    }
    abstract protected function defaultScopes(): array;
    protected function scopeSeparator(): string
    {
        return ' ';
    }

    public function getAuthorizationUrl(array $options = []): string
    {
        $url = $this->oauth->getAuthorizationUrl($options);
        $this->lastState = $this->oauth->getState();
        return $url;
    }

    public function getState(): ?string
    {
        return $this->lastState;
    }

    public function fetchAccessToken(string $code): array
    {
        try {
            $token = $this->oauth->getAccessToken('authorization_code', ['code' => $code]);
        } catch (Throwable $e) {
            throw new SocialLoginException('Failed to get access token: ' . $e->getMessage(), 0, $e);
        }

        return [
            'access_token' => $token->getToken(),
            'refresh_token' => $token->getRefreshToken(),
            'expires' => $token->getExpires(),
            'values' => $token->getValues(),
        ];
    }

    public function fetchUser(string $accessToken): User
    {
        try {
            return $this->doFetchUser($accessToken);
        } catch (Throwable $e) {
            throw new SocialLoginException('Failed to fetch user profile: ' . $e->getMessage(), 0, $e);
        }
    }

    abstract protected function doFetchUser(string $accessToken): User;
}
