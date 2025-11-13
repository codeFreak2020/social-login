# Social Login (Framework-agnostic)

A tiny, framework-agnostic PHP library to handle OAuth2 social login with multiple providers. It uses `league/oauth2-client` under the hood and ships with **6 built-in providers** plus full extensibility for any OAuth2 service.

## ✨ Features

- 🔐 **6 Built-in Providers**: Google, GitHub, Facebook, LinkedIn, Microsoft, Twitter/X
- 🔌 **Fully Extensible**: Add any OAuth2 provider in minutes
- 🚀 **Framework-agnostic**: Works with any PHP application
- 💎 **Laravel Support**: Auto-discovery, Facade, publishable config
- 🧪 **Well-tested**: PHPUnit test suite included
- 📦 **Zero Config**: Sensible defaults, customize as needed

## Install

Require via Composer:

```bash
composer require ravindrasingh0406/social-login
```

> Note: This package depends on `league/oauth2-client` and `guzzlehttp/guzzle`.

## Laravel usage

This package supports Laravel Package Auto-Discovery.

1) Install

```bash
composer require ravindrasingh0406/social-login
```

2) Publish config (optional)

```bash
php artisan vendor:publish --tag=social-login-config
```

This creates `config/social-login.php` so you can set credentials or use env vars:

```
SOCIAL_GOOGLE_CLIENT_ID=...
SOCIAL_GOOGLE_CLIENT_SECRET=...
SOCIAL_GOOGLE_REDIRECT_URI=https://your-app.test/auth/callback?provider=google

SOCIAL_GITHUB_CLIENT_ID=...
SOCIAL_GITHUB_CLIENT_SECRET=...
SOCIAL_GITHUB_REDIRECT_URI=https://your-app.test/auth/callback?provider=github
```

3) Use via Facade or DI

```php
use SocialLogin\Laravel\Facades\SocialLogin; // Facade
// or type-hint SocialLogin\Manager $social in constructors

// Step 1: redirect
$authUrl = SocialLogin::getAuthorizationUrl();
session(['oauth2state' => SocialLogin::getState()]);
return redirect()->away($authUrl);

// Step 2: callback
abort_unless(request('state') === session('oauth2state'), 400, 'Invalid state');
$token = SocialLogin::fetchAccessToken(request('code'));
$user = SocialLogin::fetchUser($token['access_token']);
```

To select a specific provider, resolve the `Manager` and call `driver('google'|'github')`:

```php
use SocialLogin\Manager;

public function redirectGoogle(Manager $social)
{
    $driver = $social->driver('google');
    return redirect()->away($driver->getAuthorizationUrl());
}
```

## Quick start (generic PHP)

```php
use SocialLogin\Manager;

$config = [
    'providers' => [
        'google' => [
            'client_id' => 'GOOGLE_CLIENT_ID',
            'client_secret' => 'GOOGLE_CLIENT_SECRET',
            'redirect_uri' => 'https://your-app.test/callback?provider=google',
            // Optional: 'scopes' => ['openid','email','profile'],
        ],
        'github' => [
            'client_id' => 'GITHUB_CLIENT_ID',
            'client_secret' => 'GITHUB_CLIENT_SECRET',
            'redirect_uri' => 'https://your-app.test/callback?provider=github',
            // Optional: 'scopes' => ['read:user','user:email'],
        ],
    ],
];

$manager = new Manager($config);
$driver = $manager->driver('google');

// 1) Redirect user to provider
$authUrl = $driver->getAuthorizationUrl();
$state = $driver->getState(); // persist this in session to validate later
header('Location: '.$authUrl);
exit;

// 2) In callback handler
$token = $driver->fetchAccessToken($_GET['code']);
$user = $driver->fetchUser($token['access_token']);
```

## Demo app

A tiny demo is available in `examples/public`:

- Copy `examples/public/config.sample.php` to `examples/public/config.php` and fill credentials
- Serve the folder, e.g.:

```bash
php -S localhost:8000 -t examples/public
```

Then open http://localhost:8000 in your browser.

## Supported Providers

| Provider | Name | Default Scopes |
|----------|------|----------------|
| Google | `google` | `openid`, `email`, `profile` |
| GitHub | `github` | `read:user`, `user:email` |
| Facebook | `facebook` | `email`, `public_profile` |
| LinkedIn | `linkedin` | `openid`, `profile`, `email` |
| Microsoft | `microsoft` | `openid`, `profile`, `email`, `User.Read` |
| Twitter/X | `twitter` | `tweet.read`, `users.read` |

**Want to add more?** See [EXTENDING.md](EXTENDING.md) for a complete guide on adding any OAuth2 provider.

### Quick Example: Facebook

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
$authUrl = $driver->getAuthorizationUrl();
```

## Extensibility

### Adding Custom Providers

This package is designed to be **fully extensible**. Add any OAuth2 provider in 3 ways:

#### Method 1: Register globally (recommended)

```php
use SocialLogin\Manager;
use App\OAuth\DiscordProvider;

Manager::extend('discord', DiscordProvider::class);

$driver = $manager->driver('discord');
```

#### Method 2: Specify in config

```php
$config = [
    'providers' => [
        'discord' => [
            'driver' => \App\OAuth\DiscordProvider::class,
            'client_id' => '...',
            'client_secret' => '...',
            'redirect_uri' => '...',
        ],
    ],
];
```

#### Method 3: Create a provider class

```php
use SocialLogin\Providers\AbstractOAuth2Provider;
use SocialLogin\DTO\User;

class DiscordProvider extends AbstractOAuth2Provider
{
    protected function authorizeUrl(): string
    {
        return 'https://discord.com/api/oauth2/authorize';
    }

    protected function tokenUrl(): string
    {
        return 'https://discord.com/api/oauth2/token';
    }

    protected function defaultScopes(): array
    {
        return ['identify', 'email'];
    }

    protected function doFetchUser(string $accessToken): User
    {
        // Fetch and map user data
        $resp = $this->http->get('https://discord.com/api/users/@me', [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken],
        ]);
        $data = json_decode((string) $resp->getBody(), true);
        
        return new User(
            id: $data['id'],
            name: $data['username'],
            email: $data['email'] ?? null,
            avatar: "https://cdn.discordapp.com/avatars/{$data['id']}/{$data['avatar']}.png",
            raw: $data,
            provider: 'discord'
        );
    }
}
```

**📖 Full Guide**: See [EXTENDING.md](EXTENDING.md) for complete documentation, real-world examples (Slack, Apple, Discord), and testing strategies.

## More Providers

More can be added by implementing `SocialLogin\\Contracts\\ProviderDriver` and extending `Providers\\AbstractOAuth2Provider`.

## License

MIT
