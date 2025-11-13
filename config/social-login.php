<?php

return [
    // Example configuration. You can override via env or config files in your Laravel app.
    'providers' => [
        'google' => [
            'client_id' => env('SOCIAL_GOOGLE_CLIENT_ID', ''),
            'client_secret' => env('SOCIAL_GOOGLE_CLIENT_SECRET', ''),
            'redirect_uri' => env('SOCIAL_GOOGLE_REDIRECT_URI', ''),
            // 'scopes' => ['openid', 'email', 'profile'],
        ],
        'github' => [
            'client_id' => env('SOCIAL_GITHUB_CLIENT_ID', ''),
            'client_secret' => env('SOCIAL_GITHUB_CLIENT_SECRET', ''),
            'redirect_uri' => env('SOCIAL_GITHUB_REDIRECT_URI', ''),
            // 'scopes' => ['read:user', 'user:email'],
        ],
        
        /*
        |--------------------------------------------------------------------------
        | Custom OAuth2 Service Example
        |--------------------------------------------------------------------------
        |
        | You can use your own OAuth2 authentication server as a login provider.
        | Perfect for enterprise SSO, multi-tenant applications, or custom auth.
        |
        | See CUSTOM_OAUTH2.md for complete documentation.
        |
        */
        
        // 'my-oauth-service' => [
        //     'driver' => \SocialLogin\Providers\GenericOAuth2Provider::class,
        //     'client_id' => env('CUSTOM_OAUTH_CLIENT_ID', ''),
        //     'client_secret' => env('CUSTOM_OAUTH_CLIENT_SECRET', ''),
        //     'redirect_uri' => env('CUSTOM_OAUTH_REDIRECT_URI', env('APP_URL') . '/auth/custom/callback'),
        //     
        //     // Your OAuth2 server endpoints
        //     'authorize_url' => env('CUSTOM_OAUTH_AUTHORIZE_URL', ''),
        //     'token_url' => env('CUSTOM_OAUTH_TOKEN_URL', ''),
        //     'userinfo_url' => env('CUSTOM_OAUTH_USERINFO_URL', ''),
        //     
        //     // Optional: Scopes
        //     'scopes' => explode(',', env('CUSTOM_OAUTH_SCOPES', 'openid,profile,email')),
        //     
        //     // Optional: Field mapping if your API uses different field names
        //     // 'user_id_field' => 'user_id',
        //     // 'user_name_field' => 'full_name',
        //     // 'user_email_field' => 'email',
        //     // 'user_avatar_field' => 'avatar_url',
        //     
        //     // Optional: Provider name in User DTO
        //     'provider_name' => env('CUSTOM_OAUTH_PROVIDER_NAME', 'custom-oauth'),
        // ],
    ],
];
