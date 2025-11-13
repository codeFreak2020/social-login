<?php

namespace SocialLogin\Tests;

use PHPUnit\Framework\TestCase;
use SocialLogin\Contracts\ProviderDriver;
use SocialLogin\DTO\User;
use SocialLogin\Exceptions\SocialLoginException;
use SocialLogin\Manager;
use SocialLogin\Providers\AbstractOAuth2Provider;
use SocialLogin\Providers\FacebookProvider;
use SocialLogin\Providers\LinkedInProvider;
use SocialLogin\Providers\MicrosoftProvider;
use SocialLogin\Providers\TwitterProvider;

class ExtensibilityTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clear custom providers between tests
        $reflection = new \ReflectionClass(Manager::class);
        $property = $reflection->getProperty('customProviders');
        $property->setAccessible(true);
        $property->setValue([]);
    }

    public function test_built_in_providers_are_available()
    {
        $available = Manager::availableProviders();
        
        $this->assertContains('google', $available);
        $this->assertContains('github', $available);
        $this->assertContains('facebook', $available);
        $this->assertContains('linkedin', $available);
        $this->assertContains('microsoft', $available);
        $this->assertContains('twitter', $available);
    }

    public function test_can_register_custom_provider()
    {
        Manager::extend('discord', TestDiscordProvider::class);
        
        $available = Manager::availableProviders();
        $this->assertContains('discord', $available);
    }

    public function test_custom_provider_can_be_instantiated()
    {
        Manager::extend('discord', TestDiscordProvider::class);
        
        $config = [
            'providers' => [
                'discord' => [
                    'client_id' => 'test_id',
                    'client_secret' => 'test_secret',
                    'redirect_uri' => 'http://localhost/callback',
                ],
            ],
        ];
        
        $manager = new Manager($config);
        $driver = $manager->driver('discord');
        
        $this->assertInstanceOf(TestDiscordProvider::class, $driver);
        $this->assertInstanceOf(ProviderDriver::class, $driver);
    }

    public function test_config_can_specify_custom_driver_class()
    {
        $config = [
            'providers' => [
                'custom' => [
                    'driver' => TestDiscordProvider::class,
                    'client_id' => 'test_id',
                    'client_secret' => 'test_secret',
                    'redirect_uri' => 'http://localhost/callback',
                ],
            ],
        ];
        
        $manager = new Manager($config);
        $driver = $manager->driver('custom');
        
        $this->assertInstanceOf(TestDiscordProvider::class, $driver);
    }

    public function test_extend_throws_exception_for_invalid_provider_class()
    {
        $this->expectException(SocialLoginException::class);
        $this->expectExceptionMessageMatches('/must implement/');
        
        Manager::extend('invalid', \stdClass::class);
    }

    public function test_config_driver_throws_exception_for_invalid_class()
    {
        $this->expectException(SocialLoginException::class);
        
        $config = [
            'providers' => [
                'custom' => [
                    'driver' => \stdClass::class,
                    'client_id' => 'test',
                    'client_secret' => 'test',
                    'redirect_uri' => 'http://localhost',
                ],
            ],
        ];
        
        $manager = new Manager($config);
        $manager->driver('custom');
    }

    public function test_facebook_provider_exists()
    {
        $config = [
            'providers' => [
                'facebook' => [
                    'client_id' => 'fb_id',
                    'client_secret' => 'fb_secret',
                    'redirect_uri' => 'http://localhost/callback',
                ],
            ],
        ];
        
        $manager = new Manager($config);
        $driver = $manager->driver('facebook');
        
        $this->assertInstanceOf(FacebookProvider::class, $driver);
    }

    public function test_linkedin_provider_exists()
    {
        $config = [
            'providers' => [
                'linkedin' => [
                    'client_id' => 'li_id',
                    'client_secret' => 'li_secret',
                    'redirect_uri' => 'http://localhost/callback',
                ],
            ],
        ];
        
        $manager = new Manager($config);
        $driver = $manager->driver('linkedin');
        
        $this->assertInstanceOf(LinkedInProvider::class, $driver);
    }

    public function test_microsoft_provider_exists()
    {
        $config = [
            'providers' => [
                'microsoft' => [
                    'client_id' => 'ms_id',
                    'client_secret' => 'ms_secret',
                    'redirect_uri' => 'http://localhost/callback',
                ],
            ],
        ];
        
        $manager = new Manager($config);
        $driver = $manager->driver('microsoft');
        
        $this->assertInstanceOf(MicrosoftProvider::class, $driver);
    }

    public function test_twitter_provider_exists()
    {
        $config = [
            'providers' => [
                'twitter' => [
                    'client_id' => 'tw_id',
                    'client_secret' => 'tw_secret',
                    'redirect_uri' => 'http://localhost/callback',
                ],
            ],
        ];
        
        $manager = new Manager($config);
        $driver = $manager->driver('twitter');
        
        $this->assertInstanceOf(TwitterProvider::class, $driver);
    }

    public function test_custom_provider_can_generate_auth_url()
    {
        Manager::extend('discord', TestDiscordProvider::class);
        
        $config = [
            'providers' => [
                'discord' => [
                    'client_id' => 'discord_client_id',
                    'client_secret' => 'discord_secret',
                    'redirect_uri' => 'http://localhost/callback',
                ],
            ],
        ];
        
        $manager = new Manager($config);
        $driver = $manager->driver('discord');
        $authUrl = $driver->getAuthorizationUrl();
        
        $this->assertStringContainsString('discord.com/api/oauth2/authorize', $authUrl);
        $this->assertStringContainsString('client_id=discord_client_id', $authUrl);
        $this->assertNotNull($driver->getState());
    }

    public function test_unknown_provider_throws_helpful_exception()
    {
        $config = [
            'providers' => [
                'unknown' => [
                    'client_id' => 'test',
                    'client_secret' => 'test',
                    'redirect_uri' => 'http://localhost',
                ],
            ],
        ];
        
        $manager = new Manager($config);
        
        $this->expectException(SocialLoginException::class);
        $this->expectExceptionMessageMatches('/Unsupported provider: unknown/');
        $this->expectExceptionMessageMatches('/Manager::extend/');
        
        $manager->driver('unknown');
    }

    public function test_multiple_custom_providers_can_coexist()
    {
        Manager::extend('discord', TestDiscordProvider::class);
        Manager::extend('slack', TestSlackProvider::class);
        
        $config = [
            'providers' => [
                'discord' => [
                    'client_id' => 'discord_id',
                    'client_secret' => 'discord_secret',
                    'redirect_uri' => 'http://localhost/discord',
                ],
                'slack' => [
                    'client_id' => 'slack_id',
                    'client_secret' => 'slack_secret',
                    'redirect_uri' => 'http://localhost/slack',
                ],
            ],
        ];
        
        $manager = new Manager($config);
        
        $discordDriver = $manager->driver('discord');
        $slackDriver = $manager->driver('slack');
        
        $this->assertInstanceOf(TestDiscordProvider::class, $discordDriver);
        $this->assertInstanceOf(TestSlackProvider::class, $slackDriver);
        
        $this->assertStringContainsString('discord.com', $discordDriver->getAuthorizationUrl());
        $this->assertStringContainsString('slack.com', $slackDriver->getAuthorizationUrl());
    }
}

// Test provider classes

class TestDiscordProvider extends AbstractOAuth2Provider
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
        return new User(
            id: 'test_discord_id',
            name: 'Discord User',
            email: 'user@discord.test',
            avatar: null,
            raw: [],
            provider: 'discord'
        );
    }
}

class TestSlackProvider extends AbstractOAuth2Provider
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
        return ['users:read'];
    }

    protected function doFetchUser(string $accessToken): User
    {
        return new User(
            id: 'test_slack_id',
            name: 'Slack User',
            email: 'user@slack.test',
            avatar: null,
            raw: [],
            provider: 'slack'
        );
    }
}
