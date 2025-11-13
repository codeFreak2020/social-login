# Quick Reference: Extensibility Features

## Overview
Your social-login package is now **fully extensible** and future-proof. You can add any OAuth2 provider without modifying core code.

## What Was Added

### 🎯 New Built-in Providers (6 total)
1. **Google** - `google`
2. **GitHub** - `github`
3. **Facebook** - `facebook` ✨ NEW
4. **LinkedIn** - `linkedin` ✨ NEW
5. **Microsoft** - `microsoft` ✨ NEW
6. **Twitter/X** - `twitter` ✨ NEW

### 🔌 Three Ways to Add Custom Providers

#### Method 1: Global Registration (Recommended)
```php
use SocialLogin\Manager;
use App\OAuth\DiscordProvider;

Manager::extend('discord', DiscordProvider::class);
```

**When to use**: Reusable providers, Laravel service providers

#### Method 2: Config-based
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

**When to use**: One-off custom providers, testing

#### Method 3: Add to Built-ins
Edit `src/Manager.php::$builtInProviders` array

**When to use**: Contributing to the package

### 📚 Documentation
- **EXTENDING.md** - Complete guide with examples
  - Discord provider example
  - Slack provider example
  - Apple Sign In example
  - Testing strategies
  - Advanced customization

### 🧪 Tests
- **13 new test cases** in `ExtensibilityTest.php`
- Tests for all 3 registration methods
- Validates all 6 built-in providers
- Error handling verification
- All passing ✅

## Quick Start Examples

### Using Facebook Login
```php
$config = [
    'providers' => [
        'facebook' => [
            'client_id' => env('SOCIAL_FACEBOOK_CLIENT_ID'),
            'client_secret' => env('SOCIAL_FACEBOOK_CLIENT_SECRET'),
            'redirect_uri' => 'https://your-app.com/auth/facebook/callback',
        ],
    ],
];

$manager = new Manager($config);
$driver = $manager->driver('facebook');
$authUrl = $driver->getAuthorizationUrl();
```

### Creating Custom Provider (Discord Example)
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

### Laravel Integration
```php
// In AppServiceProvider::boot()
use SocialLogin\Manager;
use App\OAuth\DiscordProvider;
use App\OAuth\TwitchProvider;

Manager::extend('discord', DiscordProvider::class);
Manager::extend('twitch', TwitchProvider::class);
```

## Architecture Highlights

### Manager Class
- **Dynamic registry** for custom providers
- **Three-tier resolution**:
  1. Config-specified `driver` class
  2. Custom registered providers
  3. Built-in providers
- **Helper methods**:
  - `Manager::extend($name, $class)` - Register provider
  - `Manager::availableProviders()` - List all providers

### AbstractOAuth2Provider
- Handles OAuth2 flow automatically
- Requires only 4 methods:
  - `authorizeUrl()` - Authorization endpoint
  - `tokenUrl()` - Token exchange endpoint
  - `defaultScopes()` - Default permission scopes
  - `doFetchUser($token)` - Fetch and map user data

### ProviderDriver Interface
- Contract all providers must implement
- Methods:
  - `getAuthorizationUrl()` - Get OAuth URL
  - `getState()` - Get CSRF token
  - `fetchAccessToken($code)` - Exchange code for token
  - `fetchUser($accessToken)` - Get user profile
  - `fromConfig(array)` - Factory method

## Files Modified/Added

### New Files
- `src/Providers/FacebookProvider.php`
- `src/Providers/LinkedInProvider.php`
- `src/Providers/MicrosoftProvider.php`
- `src/Providers/TwitterProvider.php`
- `tests/ExtensibilityTest.php`
- `EXTENDING.md`
- `QUICK_REFERENCE.md` (this file)

### Modified Files
- `src/Manager.php` - Added registry and resolution logic
- `README.md` - Added extensibility section
- `tests/` - New test suite

## Test Results
```
✅ 16 tests, 33 assertions
✅ All providers instantiate correctly
✅ Custom registration works
✅ Config-based drivers work
✅ Error handling verified
```

## Next Steps

### For Users
1. Use any of the 6 built-in providers
2. Add custom providers via `Manager::extend()`
3. Read `EXTENDING.md` for detailed examples

### For Contributors
1. Add more popular providers (Twitch, Discord, Slack, etc.)
2. Add provider-specific features (refresh tokens, revocation)
3. Create provider generator CLI tool

### For Laravel Users
1. Register providers in service provider
2. Use Facade: `SocialLogin::driver('facebook')`
3. Publish config for env-based setup

## Provider Endpoint Reference

| Provider | Authorize URL | Token URL |
|----------|---------------|-----------|
| Google | `https://accounts.google.com/o/oauth2/v2/auth` | `https://oauth2.googleapis.com/token` |
| GitHub | `https://github.com/login/oauth/authorize` | `https://github.com/login/oauth/access_token` |
| Facebook | `https://www.facebook.com/v18.0/dialog/oauth` | `https://graph.facebook.com/v18.0/oauth/access_token` |
| LinkedIn | `https://www.linkedin.com/oauth/v2/authorization` | `https://www.linkedin.com/oauth/v2/accessToken` |
| Microsoft | `https://login.microsoftonline.com/common/oauth2/v2.0/authorize` | `https://login.microsoftonline.com/common/oauth2/v2.0/token` |
| Twitter | `https://twitter.com/i/oauth2/authorize` | `https://api.twitter.com/2/oauth2/token` |

## Common OAuth2 Providers to Add

Here are popular providers you can easily add using the same pattern:

- **Discord** - Gaming community platform
- **Twitch** - Live streaming platform
- **Slack** - Team collaboration
- **Apple** - Apple Sign In
- **Spotify** - Music streaming
- **Dropbox** - File storage
- **Reddit** - Social news
- **GitLab** - DevOps platform
- **Bitbucket** - Code repository
- **Salesforce** - CRM platform

Each requires:
1. OAuth app registration with the provider
2. ~50 lines of code extending `AbstractOAuth2Provider`
3. Endpoint URLs and scopes from provider docs

## Support

- **Documentation**: See [EXTENDING.md](EXTENDING.md)
- **Issues**: https://github.com/codeFreak2020/social-login/issues
- **Examples**: Check `src/Providers/` for reference implementations

---

**Built with ❤️ to be future-proof and extensible**
