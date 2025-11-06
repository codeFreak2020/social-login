<?php

declare(strict_types=1);

namespace SocialLogin\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string getAuthorizationUrl(array $options = [])
 * @method static string|null getState()
 * @method static array fetchAccessToken(string $code)
 * @method static \SocialLogin\DTO\User fetchUser(string $accessToken)
 */
class SocialLogin extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'social-login';
    }
}
