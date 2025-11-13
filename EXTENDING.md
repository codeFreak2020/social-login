# Adding Custom OAuth2 Providers

This guide explains how to add any third-party OAuth2 login provider to the social-login package.

## Table of Contents
- [Quick Start](#quick-start)
- [Built-in Providers](#built-in-providers)
- [Three Ways to Add a Provider](#three-ways-to-add-a-provider)
- [Creating a Custom Provider Class](#creating-a-custom-provider-class)
- [Real-World Examples](#real-world-examples)
- [Testing Your Provider](#testing-your-provider)

---

## Quick Start

The package is designed to be **fully extensible**. You can add any OAuth2 provider in three ways:

1. **Use built-in providers** (Google, GitHub, Facebook, LinkedIn, Microsoft, Twitter)
2. **Register a custom provider globally** via `Manager::extend()`
3. **Specify a custom driver class** in your config

---

## Built-in Providers

The following providers work out of the box:

| Provider | Name | Scopes |
|----------|------|--------|
| Google | `google` | `openid`, `email`, `profile` |
| GitHub | `github` | `read:user`, `user:email` |
| Facebook | `facebook` | `email`, `public_profile` |
| LinkedIn | `linkedin` | `openid`, `profile`, `email` |
| Microsoft | `microsoft` | `openid`, `profile`, `email`, `User.Read` |
| Twitter/X | `twitter` | `tweet.read`, `users.read` |

**Usage example:**

```php
$config = [
    'providers' => [
        'facebook' => [
            'client_id' => 'YOUR_FACEBOOK_APP_ID',
            'client_secret' => 'YOUR_FACEBOOK_SECRET',
            'redirect_uri' => 'https://your-app.com/auth/facebook/callback',
        ],
    ],
];

$manager = new Manager($config);
$driver = $manager->driver('facebook');
```

---

## Three Ways to Add a Provider

### Method 1: Register Globally (Recommended for reusable providers)

Create your provider class, then register it globally:

```php
use SocialLogin\Manager;
use App\OAuth\DiscordProvider;

// Register once (e.g., in a service provider or bootstrap file)
Manager::extend('discord', DiscordProvider::class);

// Now use it anywhere
$manager = new Manager($config);
$driver = $manager->driver('discord');
```

**Laravel example** (in `AppServiceProvider::boot()`):

```php
use SocialLogin\Manager;
use App\OAuth\DiscordProvider;

public function boot()
{
    Manager::extend('discord', DiscordProvider::class);
    Manager::extend('twitch', TwitchProvider::class);
}
```

---

### Method 2: Specify Driver in Config (One-off custom providers)

Pass the provider class directly in the configuration:

```php
use App\OAuth\DiscordProvider;

$config = [
    'providers' => [
        'discord' => [
            'driver' => DiscordProvider::class,  // Custom driver
            'client_id' => 'DISCORD_CLIENT_ID',
            'client_secret' => 'DISCORD_SECRET',
            'redirect_uri' => 'https://your-app.com/auth/discord/callback',
        ],
    ],
];

$manager = new Manager($config);
$driver = $manager->driver('discord');
```

---

### Method 3: Add to Built-in Providers (For package contributors)

If you're contributing a widely-used provider to the package itself:

1. Create the provider class in `src/Providers/YourProvider.php`
2. Add it to `Manager::$builtInProviders`:

```php
private static array $builtInProviders = [
    'google' => GoogleProvider::class,
    'github' => GithubProvider::class,
    'discord' => Providers\DiscordProvider::class,  // Add here
];
```

3. Submit a pull request!

---

## Creating a Custom Provider Class

All providers must:
1. Extend `AbstractOAuth2Provider` (or implement `ProviderDriver` interface)
2. Define OAuth2 endpoints and scopes
3. Map the provider's user response to our `User` DTO

### Step-by-Step Example: Discord Provider

```php
<?php

namespace App\OAuth;

use SocialLogin\Providers\AbstractOAuth2Provider;
use SocialLogin\DTO\User;

class DiscordProvider extends AbstractOAuth2Provider
{
    /**
     * OAuth2 authorization endpoint
     */
    protected function authorizeUrl(): string
    {
        return 'https://discord.com/api/oauth2/authorize';
    }

    /**
     * OAuth2 token exchange endpoint
     */
    protected function tokenUrl(): string
    {
        return 'https://discord.com/api/oauth2/token';
    }

    /**
     * User profile endpoint (optional, can return empty string)
     */
    protected function resourceOwnerUrl(): string
    {
        return 'https://discord.com/api/users/@me';
    }

    /**
     * Default OAuth2 scopes to request
     */
    protected function defaultScopes(): array
    {
        return ['identify', 'email'];
    }

    /**
     * Scope separator (default is space, Discord uses space)
     */
    protected function scopeSeparator(): string
    {
        return ' ';
    }

    /**
     * Fetch and map user profile to User DTO
     * 
     * @param string $accessToken Bearer token
     * @return User
     */
    protected function doFetchUser(string $accessToken): User
    {
        $resp = $this->http->get('https://discord.com/api/users/@me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);

        $data = json_decode((string) $resp->getBody(), true) ?: [];
        
        $id = $data['id'] ?? null;
        $username = $data['username'] ?? null;
        $discriminator = $data['discriminator'] ?? null;
        $email = $data['email'] ?? null;
        $avatar = isset($data['avatar']) 
            ? "https://cdn.discordapp.com/avatars/{$id}/{$data['avatar']}.png"
            : null;

        // Build display name
        $name = $discriminator !== '0' 
            ? "{$username}#{$discriminator}" 
            : $username;

        return new User(
            id: (string) $id,
            name: $name,
            email: $email,
            avatar: $avatar,
            raw: $data,
            provider: 'discord'
        );
    }
}
```

---

## Real-World Examples

### Slack Provider

```php
<?php

namespace App\OAuth;

use SocialLogin\Providers\AbstractOAuth2Provider;
use SocialLogin\DTO\User;

class SlackProvider extends AbstractOAuth2Provider
{
    protected function authorizeUrl(): string
    {
        return 'https://slack.com/oauth/v2/authorize';
    }

    protected function tokenUrl(): string
    {
        return 'https://slack.com/api/oauth.v2.access';
    }

    protected function defaultScopes(): array
    {
        return ['users:read', 'users:read.email'];
    }

    protected function doFetchUser(string $accessToken): User
    {
        $resp = $this->http->get('https://slack.com/api/users.identity', [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken],
        ]);

        $response = json_decode((string) $resp->getBody(), true) ?: [];
        $user = $response['user'] ?? [];
        
        return new User(
            id: (string) ($user['id'] ?? ''),
            name: $user['name'] ?? null,
            email: $user['email'] ?? null,
            avatar: $user['image_192'] ?? null,
            raw: $response,
            provider: 'slack'
        );
    }
}
```

### Apple Sign In Provider

```php
<?php

namespace App\OAuth;

use SocialLogin\Providers\AbstractOAuth2Provider;
use SocialLogin\DTO\User;

class AppleProvider extends AbstractOAuth2Provider
{
    protected function authorizeUrl(): string
    {
        return 'https://appleid.apple.com/auth/authorize';
    }

    protected function tokenUrl(): string
    {
        return 'https://appleid.apple.com/auth/token';
    }

    protected function defaultScopes(): array
    {
        return ['name', 'email'];
    }

    protected function doFetchUser(string $accessToken): User
    {
        // Apple doesn't provide a user endpoint
        // User data comes in the ID token (JWT)
        // You'd need to decode the JWT here
        
        // For simplicity, returning minimal data
        // In production, decode the ID token from $this->oauth->getAccessToken() values
        
        return new User(
            id: 'apple_user_id',
            name: null,
            email: null,
            avatar: null,
            raw: [],
            provider: 'apple'
        );
    }
}
```

---

## Testing Your Provider

### 1. Unit Test

```php
use PHPUnit\Framework\TestCase;
use SocialLogin\Manager;
use App\OAuth\DiscordProvider;

class DiscordProviderTest extends TestCase
{
    public function test_discord_provider_registration()
    {
        Manager::extend('discord', DiscordProvider::class);
        
        $config = [
            'providers' => [
                'discord' => [
                    'client_id' => 'test',
                    'client_secret' => 'test',
                    'redirect_uri' => 'http://localhost/callback',
                ],
            ],
        ];
        
        $manager = new Manager($config);
        $driver = $manager->driver('discord');
        
        $this->assertInstanceOf(DiscordProvider::class, $driver);
    }

    public function test_authorization_url_generated()
    {
        Manager::extend('discord', DiscordProvider::class);
        
        $config = [
            'providers' => [
                'discord' => [
                    'client_id' => 'test_client_id',
                    'client_secret' => 'test_secret',
                    'redirect_uri' => 'http://localhost/callback',
                ],
            ],
        ];
        
        $manager = new Manager($config);
        $driver = $manager->driver('discord');
        $url = $driver->getAuthorizationUrl();
        
        $this->assertStringContainsString('discord.com/api/oauth2/authorize', $url);
        $this->assertStringContainsString('client_id=test_client_id', $url);
    }
}
```

### 2. Manual Integration Test

```php
// In your demo/test script
use SocialLogin\Manager;
use App\OAuth\DiscordProvider;

Manager::extend('discord', DiscordProvider::class);

$config = [
    'providers' => [
        'discord' => [
            'client_id' => $_ENV['DISCORD_CLIENT_ID'],
            'client_secret' => $_ENV['DISCORD_CLIENT_SECRET'],
            'redirect_uri' => 'http://localhost:8000/callback.php?provider=discord',
        ],
    ],
];

$manager = new Manager($config);

// Step 1: Generate auth URL
$driver = $manager->driver('discord');
$authUrl = $driver->getAuthorizationUrl();
$_SESSION['oauth2state'] = $driver->getState();

echo "Visit: $authUrl\n";

// Step 2: In callback
$code = $_GET['code'];
$token = $driver->fetchAccessToken($code);
$user = $driver->fetchUser($token['access_token']);

var_dump($user);
```

---

## Advanced: Custom OAuth2 Flows

If a provider uses a non-standard OAuth2 flow, you can override methods in `AbstractOAuth2Provider`:

```php
class CustomProvider extends AbstractOAuth2Provider
{
    /**
     * Override to customize token exchange
     */
    public function fetchAccessToken(string $code): array
    {
        // Custom logic here
        $token = $this->oauth->getAccessToken('authorization_code', [
            'code' => $code,
            'custom_param' => 'value',
        ]);
        
        return [
            'access_token' => $token->getToken(),
            'refresh_token' => $token->getRefreshToken(),
            'expires' => $token->getExpires(),
            'values' => $token->getValues(),
        ];
    }

    /**
     * Override to customize authorization URL
     */
    public function getAuthorizationUrl(array $options = []): string
    {
        $options['response_mode'] = 'form_post';  // Custom param
        return parent::getAuthorizationUrl($options);
    }
}
```

---

## Summary

✅ **Built-in providers**: Google, GitHub, Facebook, LinkedIn, Microsoft, Twitter  
✅ **Extensible**: Add any OAuth2 provider in 3 different ways  
✅ **Simple**: Extend `AbstractOAuth2Provider` and implement 4 methods  
✅ **Flexible**: Override any behavior for custom flows  

**Next steps:**
- Check `src/Providers/` for reference implementations
- See `examples/` for working demos
- Contribute your provider back to the package!

---

## Need Help?

- **Issues**: https://github.com/codeFreak2020/social-login/issues
- **Discussions**: https://github.com/codeFreak2020/social-login/discussions
- **Provider docs**: Most OAuth2 providers have developer documentation with endpoint URLs and required scopes
