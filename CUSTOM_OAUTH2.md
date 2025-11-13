# Using Your Own OAuth2 Service

This guide shows you how to use this package with **your own OAuth2 authentication server** as a social login provider.

## Table of Contents
- [Quick Start](#quick-start)
- [Building Your OAuth2 Server](#building-your-oauth2-server)
- [Configuration](#configuration)
- [Environment Variables](#environment-variables)
- [Laravel Integration](#laravel-integration)
- [Complete Example](#complete-example)
- [Field Mapping](#field-mapping)
- [Advanced Options](#advanced-options)

---

## Quick Start

The `GenericOAuth2Provider` allows you to use **any OAuth2 server** as a login provider, including your own custom authentication service.

```php
use SocialLogin\Manager;
use SocialLogin\Providers\GenericOAuth2Provider;

$config = [
    'providers' => [
        'my-auth' => [
            'driver' => GenericOAuth2Provider::class,
            'client_id' => 'your_client_id',
            'client_secret' => 'your_client_secret',
            'redirect_uri' => 'https://your-app.com/auth/callback',
            
            // Your OAuth2 server endpoints
            'authorize_url' => 'https://auth.yourcompany.com/oauth/authorize',
            'token_url' => 'https://auth.yourcompany.com/oauth/token',
            'userinfo_url' => 'https://auth.yourcompany.com/api/user',
            
            'scopes' => ['openid', 'profile', 'email'],
        ],
    ],
];

$manager = new Manager($config);
$driver = $manager->driver('my-auth');
```

---

## Building Your OAuth2 Server

Your custom OAuth2 server needs to implement these **3 required endpoints**:

### 1. Authorization Endpoint (`/oauth/authorize`)
**Purpose**: Redirect users here to request authorization

**Required Parameters**:
- `client_id` - Your OAuth client ID
- `redirect_uri` - Where to redirect after authorization
- `response_type` - Always `code` for authorization code flow
- `scope` - Space-separated list of requested scopes
- `state` - CSRF protection token

**Example URL**:
```
https://auth.yourcompany.com/oauth/authorize?
  client_id=abc123&
  redirect_uri=https://app.com/callback&
  response_type=code&
  scope=openid+profile+email&
  state=random_state_token
```

**Response**: Redirect user back to `redirect_uri` with:
- `code` - Authorization code (short-lived, single use)
- `state` - Same state value sent in request

### 2. Token Endpoint (`/oauth/token`)
**Purpose**: Exchange authorization code for access token

**Required Parameters**:
- `grant_type` - Always `authorization_code`
- `code` - The authorization code from step 1
- `client_id` - Your OAuth client ID
- `client_secret` - Your OAuth client secret
- `redirect_uri` - Same redirect_uri from authorization request

**Example Request**:
```http
POST /oauth/token HTTP/1.1
Host: auth.yourcompany.com
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code&
code=AUTH_CODE&
client_id=abc123&
client_secret=secret123&
redirect_uri=https://app.com/callback
```

**Required Response** (JSON):
```json
{
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "refresh_token": "optional_refresh_token",
  "scope": "openid profile email"
}
```

### 3. UserInfo Endpoint (`/api/user` or `/oauth/userinfo`)
**Purpose**: Fetch authenticated user's profile

**Required Header**:
```http
Authorization: Bearer {access_token}
```

**Example Request**:
```http
GET /api/user HTTP/1.1
Host: auth.yourcompany.com
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

**Required Response** (JSON):
```json
{
  "id": "12345",
  "name": "John Doe",
  "email": "john@example.com",
  "avatar": "https://auth.yourcompany.com/avatars/12345.jpg",
  "verified": true,
  "created_at": "2025-01-01T00:00:00Z"
}
```

**Minimum Required Fields**:
- `id` (or `sub`) - Unique user identifier
- `email` - User's email address (recommended)
- `name` - User's display name (recommended)

---

## Configuration

### Basic Configuration

```php
$config = [
    'providers' => [
        'my-oauth-service' => [
            'driver' => \SocialLogin\Providers\GenericOAuth2Provider::class,
            
            // OAuth Client Credentials
            'client_id' => env('MY_OAUTH_CLIENT_ID'),
            'client_secret' => env('MY_OAUTH_CLIENT_SECRET'),
            'redirect_uri' => env('MY_OAUTH_REDIRECT_URI'),
            
            // Your OAuth2 Server Endpoints
            'authorize_url' => 'https://auth.yourcompany.com/oauth/authorize',
            'token_url' => 'https://auth.yourcompany.com/oauth/token',
            'userinfo_url' => 'https://auth.yourcompany.com/api/user',
            
            // Scopes (optional)
            'scopes' => ['openid', 'profile', 'email'],
        ],
    ],
];
```

### Full Configuration with All Options

```php
$config = [
    'providers' => [
        'my-oauth-service' => [
            'driver' => \SocialLogin\Providers\GenericOAuth2Provider::class,
            
            // Required
            'client_id' => 'your_client_id',
            'client_secret' => 'your_client_secret',
            'redirect_uri' => 'https://your-app.com/auth/my-oauth/callback',
            'authorize_url' => 'https://auth.yourcompany.com/oauth/authorize',
            'token_url' => 'https://auth.yourcompany.com/oauth/token',
            'userinfo_url' => 'https://auth.yourcompany.com/api/user',
            
            // Optional: Scopes
            'scopes' => ['openid', 'profile', 'email', 'custom-scope'],
            'scope_separator' => ' ',  // Default: space. Some providers use ','
            
            // Optional: PKCE (Proof Key for Code Exchange)
            'pkce_method' => 'S256',  // 'S256', 'plain', or null
            
            // Optional: Field Mapping (if your API uses different field names)
            'user_id_field' => 'user_id',      // Default: 'id'
            'user_name_field' => 'full_name',  // Default: 'name'
            'user_email_field' => 'email',     // Default: 'email'
            'user_avatar_field' => 'photo',    // Default: 'avatar'
            
            // Optional: Provider name in User DTO
            'provider_name' => 'my-company-sso',  // Default: 'generic'
        ],
    ],
];
```

---

## Environment Variables

### .env Example

```bash
# Your Custom OAuth2 Service
MY_OAUTH_CLIENT_ID=your_client_id_from_oauth_server
MY_OAUTH_CLIENT_SECRET=your_client_secret_from_oauth_server
MY_OAUTH_REDIRECT_URI=https://your-app.com/auth/my-oauth/callback

# Your OAuth2 Server URLs
MY_OAUTH_AUTHORIZE_URL=https://auth.yourcompany.com/oauth/authorize
MY_OAUTH_TOKEN_URL=https://auth.yourcompany.com/oauth/token
MY_OAUTH_USERINFO_URL=https://auth.yourcompany.com/api/user

# Optional
MY_OAUTH_SCOPES=openid,profile,email
```

### Using Environment Variables in Config

```php
$config = [
    'providers' => [
        'my-oauth' => [
            'driver' => \SocialLogin\Providers\GenericOAuth2Provider::class,
            'client_id' => env('MY_OAUTH_CLIENT_ID'),
            'client_secret' => env('MY_OAUTH_CLIENT_SECRET'),
            'redirect_uri' => env('MY_OAUTH_REDIRECT_URI'),
            'authorize_url' => env('MY_OAUTH_AUTHORIZE_URL'),
            'token_url' => env('MY_OAUTH_TOKEN_URL'),
            'userinfo_url' => env('MY_OAUTH_USERINFO_URL'),
            'scopes' => explode(',', env('MY_OAUTH_SCOPES', 'openid,profile,email')),
        ],
    ],
];
```

---

## Laravel Integration

### Step 1: Publish Config

```bash
php artisan vendor:publish --tag=social-login-config
```

### Step 2: Edit `config/social-login.php`

```php
<?php

return [
    'providers' => [
        // ... existing providers (google, github, etc.)
        
        // Your custom OAuth2 service
        'my-company-sso' => [
            'driver' => \SocialLogin\Providers\GenericOAuth2Provider::class,
            'client_id' => env('COMPANY_SSO_CLIENT_ID'),
            'client_secret' => env('COMPANY_SSO_CLIENT_SECRET'),
            'redirect_uri' => env('APP_URL') . '/auth/sso/callback',
            'authorize_url' => env('COMPANY_SSO_AUTHORIZE_URL'),
            'token_url' => env('COMPANY_SSO_TOKEN_URL'),
            'userinfo_url' => env('COMPANY_SSO_USERINFO_URL'),
            'scopes' => explode(',', env('COMPANY_SSO_SCOPES', 'openid,profile,email')),
        ],
    ],
];
```

### Step 3: Add to .env

```bash
COMPANY_SSO_CLIENT_ID=client_id_from_your_sso
COMPANY_SSO_CLIENT_SECRET=client_secret_from_your_sso
COMPANY_SSO_AUTHORIZE_URL=https://sso.yourcompany.com/oauth/authorize
COMPANY_SSO_TOKEN_URL=https://sso.yourcompany.com/oauth/token
COMPANY_SSO_USERINFO_URL=https://sso.yourcompany.com/api/user
COMPANY_SSO_SCOPES=openid,profile,email
```

### Step 4: Create Routes

```php
// routes/web.php
use Illuminate\Support\Facades\Route;
use SocialLogin\Laravel\Facades\SocialLogin;

Route::get('/auth/sso', function () {
    $manager = app(\SocialLogin\Manager::class);
    $driver = $manager->driver('my-company-sso');
    
    $authUrl = $driver->getAuthorizationUrl();
    session(['oauth2state' => $driver->getState()]);
    
    return redirect()->away($authUrl);
})->name('auth.sso');

Route::get('/auth/sso/callback', function () {
    abort_unless(request('state') === session('oauth2state'), 400, 'Invalid state');
    
    $manager = app(\SocialLogin\Manager::class);
    $driver = $manager->driver('my-company-sso');
    
    $token = $driver->fetchAccessToken(request('code'));
    $user = $driver->fetchUser($token['access_token']);
    
    // Find or create user in your database
    $localUser = \App\Models\User::firstOrCreate(
        ['email' => $user->email],
        [
            'name' => $user->name,
            'avatar' => $user->avatar,
            'oauth_provider' => $user->provider,
            'oauth_id' => $user->id,
        ]
    );
    
    auth()->login($localUser);
    
    return redirect('/dashboard');
})->name('auth.sso.callback');
```

---

## Complete Example

### Full OAuth2 Flow Example

```php
<?php

use SocialLogin\Manager;
use SocialLogin\Providers\GenericOAuth2Provider;

session_start();

$config = [
    'providers' => [
        'company-auth' => [
            'driver' => GenericOAuth2Provider::class,
            'client_id' => 'your_client_id',
            'client_secret' => 'your_client_secret',
            'redirect_uri' => 'https://your-app.com/callback.php',
            
            // Your OAuth2 server endpoints
            'authorize_url' => 'https://auth.company.com/oauth/authorize',
            'token_url' => 'https://auth.company.com/oauth/token',
            'userinfo_url' => 'https://auth.company.com/api/user',
            
            'scopes' => ['openid', 'profile', 'email'],
            'provider_name' => 'company-sso',
        ],
    ],
];

$manager = new Manager($config);
$driver = $manager->driver('company-auth');

// Step 1: Redirect to authorization page
if (!isset($_GET['code'])) {
    $authUrl = $driver->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $driver->getState();
    
    header('Location: ' . $authUrl);
    exit;
}

// Step 2: Handle callback
if ($_GET['state'] !== $_SESSION['oauth2state']) {
    die('Invalid state parameter');
}

try {
    // Exchange code for token
    $token = $driver->fetchAccessToken($_GET['code']);
    
    // Fetch user profile
    $user = $driver->fetchUser($token['access_token']);
    
    echo "Logged in as: {$user->name} ({$user->email})\n";
    echo "User ID: {$user->id}\n";
    echo "Provider: {$user->provider}\n";
    echo "Avatar: {$user->avatar}\n";
    
    // Store in session
    $_SESSION['user'] = [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'provider' => $user->provider,
    ];
    
} catch (\Exception $e) {
    die('Authentication failed: ' . $e->getMessage());
}
```

---

## Field Mapping

If your OAuth2 server uses different field names in the userinfo response, you can map them:

### Example: Custom Field Names

**Your API Response**:
```json
{
  "user_id": "12345",
  "full_name": "John Doe",
  "email_address": "john@company.com",
  "profile_photo": "https://cdn.company.com/photos/12345.jpg"
}
```

**Configuration**:
```php
$config = [
    'providers' => [
        'my-api' => [
            'driver' => GenericOAuth2Provider::class,
            // ... other config ...
            'user_id_field' => 'user_id',
            'user_name_field' => 'full_name',
            'user_email_field' => 'email_address',
            'user_avatar_field' => 'profile_photo',
        ],
    ],
];
```

### Fallback Behavior

The GenericOAuth2Provider has intelligent fallbacks:
- `id` field: tries config field → `id` → `sub` (OpenID standard)
- `avatar` field: tries config field → `avatar` → `picture` (OpenID standard)

---

## Advanced Options

### PKCE (Proof Key for Code Exchange)

For enhanced security, especially in public clients:

```php
'pkce_method' => 'S256',  // SHA256 hashing (recommended)
// or
'pkce_method' => 'plain', // Plain text (less secure)
// or
'pkce_method' => null,    // Disable PKCE
```

### Custom Scope Separator

Some OAuth2 servers use comma instead of space:

```php
'scope_separator' => ',',  // Facebook uses this
```

### Multiple Custom OAuth2 Services

You can configure multiple custom OAuth2 servers:

```php
$config = [
    'providers' => [
        'company-sso' => [
            'driver' => GenericOAuth2Provider::class,
            'authorize_url' => 'https://sso.company.com/oauth/authorize',
            // ...
        ],
        'partner-auth' => [
            'driver' => GenericOAuth2Provider::class,
            'authorize_url' => 'https://auth.partner.com/oauth/authorize',
            // ...
        ],
        'staging-auth' => [
            'driver' => GenericOAuth2Provider::class,
            'authorize_url' => 'https://staging-auth.company.com/oauth/authorize',
            // ...
        ],
    ],
];
```

---

## OAuth2 Server Implementation Tips

### Recommended Libraries for Building Your OAuth2 Server

**PHP**:
- [Laravel Passport](https://laravel.com/docs/passport) - Full OAuth2 server for Laravel
- [League OAuth2 Server](https://oauth2.thephpleague.com/) - Framework-agnostic OAuth2 server
- [Symfony OAuth2 Bundle](https://github.com/trikoder/oauth2-bundle)

**Node.js**:
- [node-oauth2-server](https://github.com/oauthjs/node-oauth2-server)
- [oidc-provider](https://github.com/panva/node-oidc-provider) - OpenID Connect

**Python**:
- [Authlib](https://authlib.org/)
- [Django OAuth Toolkit](https://django-oauth-toolkit.readthedocs.io/)

### Security Best Practices

1. **Always use HTTPS** for all OAuth2 endpoints
2. **Validate redirect_uri** - Only allow pre-registered URIs
3. **Short-lived authorization codes** - Expire in 1-10 minutes
4. **One-time use codes** - Invalidate after exchange
5. **State parameter** - Prevent CSRF attacks
6. **PKCE** - Use for public clients (SPAs, mobile apps)
7. **Rate limiting** - Prevent brute force attacks
8. **Audit logging** - Log all OAuth2 events

---

## Troubleshooting

### Common Issues

**Issue**: "Missing required config: authorize_url"
- **Solution**: Make sure all 3 URLs are in your config:
  - `authorize_url`
  - `token_url`
  - `userinfo_url`

**Issue**: "Invalid client" or "Client authentication failed"
- **Solution**: Double-check your `client_id` and `client_secret`

**Issue**: "Redirect URI mismatch"
- **Solution**: The `redirect_uri` in your config must exactly match the one registered in your OAuth2 server

**Issue**: "User data not loading correctly"
- **Solution**: Use field mapping if your API uses different field names:
  ```php
  'user_id_field' => 'your_id_field_name',
  'user_name_field' => 'your_name_field_name',
  ```

---

## Examples by Framework

### Laravel Passport Setup

```php
// Your OAuth2 server (using Passport)
// php artisan passport:client

$config = [
    'providers' => [
        'my-passport-server' => [
            'driver' => GenericOAuth2Provider::class,
            'client_id' => 'client-id-from-passport',
            'client_secret' => 'client-secret-from-passport',
            'redirect_uri' => 'https://client-app.com/callback',
            'authorize_url' => 'https://passport-server.com/oauth/authorize',
            'token_url' => 'https://passport-server.com/oauth/token',
            'userinfo_url' => 'https://passport-server.com/api/user',
            'scopes' => [],  // Passport uses * for all scopes
        ],
    ],
];
```

### Keycloak Setup

```php
$config = [
    'providers' => [
        'keycloak' => [
            'driver' => GenericOAuth2Provider::class,
            'client_id' => 'your-client-id',
            'client_secret' => 'your-client-secret',
            'redirect_uri' => 'https://your-app.com/callback',
            'authorize_url' => 'https://keycloak.company.com/auth/realms/master/protocol/openid-connect/auth',
            'token_url' => 'https://keycloak.company.com/auth/realms/master/protocol/openid-connect/token',
            'userinfo_url' => 'https://keycloak.company.com/auth/realms/master/protocol/openid-connect/userinfo',
            'scopes' => ['openid', 'profile', 'email'],
            'user_id_field' => 'sub',
        ],
    ],
];
```

### Auth0 Setup

```php
$config = [
    'providers' => [
        'auth0' => [
            'driver' => GenericOAuth2Provider::class,
            'client_id' => 'your-auth0-client-id',
            'client_secret' => 'your-auth0-client-secret',
            'redirect_uri' => 'https://your-app.com/callback',
            'authorize_url' => 'https://your-tenant.auth0.com/authorize',
            'token_url' => 'https://your-tenant.auth0.com/oauth/token',
            'userinfo_url' => 'https://your-tenant.auth0.com/userinfo',
            'scopes' => ['openid', 'profile', 'email'],
            'user_id_field' => 'sub',
        ],
    ],
];
```

---

## Summary

✅ Use `GenericOAuth2Provider` for any OAuth2 server  
✅ Perfect for your own custom authentication service  
✅ Full control over endpoints, scopes, and field mapping  
✅ Works with Laravel Passport, Keycloak, Auth0, and any OAuth2 server  
✅ Environment variable support for all settings  
✅ PKCE support for enhanced security  

**Next Steps**:
- Build your OAuth2 server using Laravel Passport or similar
- Configure the GenericOAuth2Provider with your endpoints
- Test the flow end-to-end
- Deploy and enjoy SSO across all your applications!

---

**Need Help?**
- OAuth2 Spec: https://oauth2.net/2/
- OpenID Connect: https://openid.net/connect/
- Issues: https://github.com/codeFreak2020/social-login/issues
