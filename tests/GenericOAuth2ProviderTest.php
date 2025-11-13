<?php

namespace SocialLogin\Tests;

use PHPUnit\Framework\TestCase;
use SocialLogin\Manager;
use SocialLogin\Providers\GenericOAuth2Provider;
use SocialLogin\DTO\User;

class GenericOAuth2ProviderTest extends TestCase
{
    public function test_can_instantiate_with_custom_endpoints()
    {
        $config = [
            'providers' => [
                'my-auth' => [
                    'driver' => GenericOAuth2Provider::class,
                    'client_id' => 'test_client',
                    'client_secret' => 'test_secret',
                    'redirect_uri' => 'http://localhost/callback',
                    'authorize_url' => 'https://auth.example.com/oauth/authorize',
                    'token_url' => 'https://auth.example.com/oauth/token',
                    'userinfo_url' => 'https://auth.example.com/api/user',
                ],
            ],
        ];
        
        $manager = new Manager($config);
        $driver = $manager->driver('my-auth');
        
        $this->assertInstanceOf(GenericOAuth2Provider::class, $driver);
    }

    public function test_generates_auth_url_with_custom_endpoint()
    {
        $config = [
            'providers' => [
                'custom' => [
                    'driver' => GenericOAuth2Provider::class,
                    'client_id' => 'custom_client_id',
                    'client_secret' => 'custom_secret',
                    'redirect_uri' => 'http://localhost/callback',
                    'authorize_url' => 'https://custom.auth.com/oauth/authorize',
                    'token_url' => 'https://custom.auth.com/oauth/token',
                    'userinfo_url' => 'https://custom.auth.com/api/user',
                    'scopes' => ['custom-scope', 'another-scope'],
                ],
            ],
        ];
        
        $manager = new Manager($config);
        $driver = $manager->driver('custom');
        $authUrl = $driver->getAuthorizationUrl();
        
        $this->assertStringContainsString('custom.auth.com/oauth/authorize', $authUrl);
        $this->assertStringContainsString('client_id=custom_client_id', $authUrl);
        $this->assertNotNull($driver->getState());
    }

    public function test_throws_exception_when_authorize_url_missing()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required config: authorize_url');
        
        $config = [
            'providers' => [
                'invalid' => [
                    'driver' => GenericOAuth2Provider::class,
                    'client_id' => 'test',
                    'client_secret' => 'test',
                    'redirect_uri' => 'http://localhost',
                    // Missing authorize_url
                    'token_url' => 'https://example.com/token',
                    'userinfo_url' => 'https://example.com/user',
                ],
            ],
        ];
        
        $manager = new Manager($config);
        $driver = $manager->driver('invalid');
        $driver->getAuthorizationUrl();
    }

    public function test_supports_custom_scope_separator()
    {
        $config = [
            'providers' => [
                'custom' => [
                    'driver' => GenericOAuth2Provider::class,
                    'client_id' => 'test',
                    'client_secret' => 'test',
                    'redirect_uri' => 'http://localhost',
                    'authorize_url' => 'https://example.com/oauth/authorize',
                    'token_url' => 'https://example.com/oauth/token',
                    'userinfo_url' => 'https://example.com/api/user',
                    'scopes' => ['scope1', 'scope2'],
                    'scope_separator' => ',',  // Comma instead of space
                ],
            ],
        ];
        
        $manager = new Manager($config);
        $driver = $manager->driver('custom');
        $authUrl = $driver->getAuthorizationUrl();
        
        // URL should contain comma-separated scopes
        $this->assertStringContainsString('scope1', $authUrl);
        $this->assertStringContainsString('scope2', $authUrl);
    }

    public function test_can_use_laravel_passport_style_config()
    {
        $config = [
            'providers' => [
                'passport' => [
                    'driver' => GenericOAuth2Provider::class,
                    'client_id' => 'passport_client',
                    'client_secret' => 'passport_secret',
                    'redirect_uri' => 'http://client.app/callback',
                    'authorize_url' => 'https://passport.server/oauth/authorize',
                    'token_url' => 'https://passport.server/oauth/token',
                    'userinfo_url' => 'https://passport.server/api/user',
                    'scopes' => [],
                ],
            ],
        ];
        
        $manager = new Manager($config);
        $driver = $manager->driver('passport');
        
        $this->assertInstanceOf(GenericOAuth2Provider::class, $driver);
        
        $authUrl = $driver->getAuthorizationUrl();
        $this->assertStringContainsString('passport.server/oauth/authorize', $authUrl);
    }

    public function test_can_use_keycloak_style_config()
    {
        $config = [
            'providers' => [
                'keycloak' => [
                    'driver' => GenericOAuth2Provider::class,
                    'client_id' => 'keycloak_client',
                    'client_secret' => 'keycloak_secret',
                    'redirect_uri' => 'http://app.com/callback',
                    'authorize_url' => 'https://keycloak.com/auth/realms/master/protocol/openid-connect/auth',
                    'token_url' => 'https://keycloak.com/auth/realms/master/protocol/openid-connect/token',
                    'userinfo_url' => 'https://keycloak.com/auth/realms/master/protocol/openid-connect/userinfo',
                    'scopes' => ['openid', 'profile', 'email'],
                    'user_id_field' => 'sub',
                ],
            ],
        ];
        
        $manager = new Manager($config);
        $driver = $manager->driver('keycloak');
        
        $this->assertInstanceOf(GenericOAuth2Provider::class, $driver);
        
        $authUrl = $driver->getAuthorizationUrl();
        $this->assertStringContainsString('keycloak.com', $authUrl);
        $this->assertStringContainsString('openid-connect/auth', $authUrl);
    }

    public function test_can_use_auth0_style_config()
    {
        $config = [
            'providers' => [
                'auth0' => [
                    'driver' => GenericOAuth2Provider::class,
                    'client_id' => 'auth0_client',
                    'client_secret' => 'auth0_secret',
                    'redirect_uri' => 'http://app.com/callback',
                    'authorize_url' => 'https://tenant.auth0.com/authorize',
                    'token_url' => 'https://tenant.auth0.com/oauth/token',
                    'userinfo_url' => 'https://tenant.auth0.com/userinfo',
                    'scopes' => ['openid', 'profile', 'email'],
                    'user_id_field' => 'sub',
                ],
            ],
        ];
        
        $manager = new Manager($config);
        $driver = $manager->driver('auth0');
        
        $this->assertInstanceOf(GenericOAuth2Provider::class, $driver);
        
        $authUrl = $driver->getAuthorizationUrl();
        $this->assertStringContainsString('tenant.auth0.com/authorize', $authUrl);
    }

    public function test_supports_custom_field_mapping()
    {
        $config = [
            'providers' => [
                'custom' => [
                    'driver' => GenericOAuth2Provider::class,
                    'client_id' => 'test',
                    'client_secret' => 'test',
                    'redirect_uri' => 'http://localhost',
                    'authorize_url' => 'https://example.com/oauth/authorize',
                    'token_url' => 'https://example.com/oauth/token',
                    'userinfo_url' => 'https://example.com/api/user',
                    'user_id_field' => 'user_id',
                    'user_name_field' => 'full_name',
                    'user_email_field' => 'email_address',
                    'user_avatar_field' => 'profile_photo',
                ],
            ],
        ];
        
        $manager = new Manager($config);
        $driver = $manager->driver('custom');
        
        $this->assertInstanceOf(GenericOAuth2Provider::class, $driver);
    }

    public function test_supports_custom_provider_name()
    {
        $config = [
            'providers' => [
                'my-sso' => [
                    'driver' => GenericOAuth2Provider::class,
                    'client_id' => 'test',
                    'client_secret' => 'test',
                    'redirect_uri' => 'http://localhost',
                    'authorize_url' => 'https://sso.company.com/oauth/authorize',
                    'token_url' => 'https://sso.company.com/oauth/token',
                    'userinfo_url' => 'https://sso.company.com/api/user',
                    'provider_name' => 'company-sso',
                ],
            ],
        ];
        
        $manager = new Manager($config);
        $driver = $manager->driver('my-sso');
        
        $this->assertInstanceOf(GenericOAuth2Provider::class, $driver);
    }

    public function test_multiple_custom_oauth_services_can_coexist()
    {
        $config = [
            'providers' => [
                'company-sso' => [
                    'driver' => GenericOAuth2Provider::class,
                    'client_id' => 'company_client',
                    'client_secret' => 'company_secret',
                    'redirect_uri' => 'http://app.com/callback',
                    'authorize_url' => 'https://sso.company.com/oauth/authorize',
                    'token_url' => 'https://sso.company.com/oauth/token',
                    'userinfo_url' => 'https://sso.company.com/api/user',
                ],
                'partner-auth' => [
                    'driver' => GenericOAuth2Provider::class,
                    'client_id' => 'partner_client',
                    'client_secret' => 'partner_secret',
                    'redirect_uri' => 'http://app.com/partner/callback',
                    'authorize_url' => 'https://auth.partner.com/oauth/authorize',
                    'token_url' => 'https://auth.partner.com/oauth/token',
                    'userinfo_url' => 'https://auth.partner.com/api/user',
                ],
            ],
        ];
        
        $manager = new Manager($config);
        
        $companyDriver = $manager->driver('company-sso');
        $partnerDriver = $manager->driver('partner-auth');
        
        $this->assertInstanceOf(GenericOAuth2Provider::class, $companyDriver);
        $this->assertInstanceOf(GenericOAuth2Provider::class, $partnerDriver);
        
        $companyUrl = $companyDriver->getAuthorizationUrl();
        $partnerUrl = $partnerDriver->getAuthorizationUrl();
        
        $this->assertStringContainsString('sso.company.com', $companyUrl);
        $this->assertStringContainsString('auth.partner.com', $partnerUrl);
    }

    public function test_works_alongside_built_in_providers()
    {
        $config = [
            'providers' => [
                'google' => [
                    'client_id' => 'google_client',
                    'client_secret' => 'google_secret',
                    'redirect_uri' => 'http://app.com/google/callback',
                ],
                'my-custom-oauth' => [
                    'driver' => GenericOAuth2Provider::class,
                    'client_id' => 'custom_client',
                    'client_secret' => 'custom_secret',
                    'redirect_uri' => 'http://app.com/custom/callback',
                    'authorize_url' => 'https://custom.auth.com/oauth/authorize',
                    'token_url' => 'https://custom.auth.com/oauth/token',
                    'userinfo_url' => 'https://custom.auth.com/api/user',
                ],
            ],
        ];
        
        $manager = new Manager($config);
        
        $googleDriver = $manager->driver('google');
        $customDriver = $manager->driver('my-custom-oauth');
        
        $this->assertInstanceOf(\SocialLogin\Providers\GoogleProvider::class, $googleDriver);
        $this->assertInstanceOf(GenericOAuth2Provider::class, $customDriver);
    }
}
