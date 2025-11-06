<?php

namespace SocialLogin\Contracts;

use SocialLogin\DTO\User;

interface ProviderDriver
{
    public function getAuthorizationUrl(array $options = []): string;
    public function getState(): ?string;
    /**
     * Exchange an authorization code for an access token structure.
     * Expected keys: access_token, refresh_token (optional), expires (int|null), values (array)
     */
    public function fetchAccessToken(string $code): array;
    /**
     * Fetch the user profile from the provider using a Bearer access token.
     */
    public function fetchUser(string $accessToken): User;
    /**
     * Factory to build provider from configuration array.
     */
    public static function fromConfig(array $config): self;
}
