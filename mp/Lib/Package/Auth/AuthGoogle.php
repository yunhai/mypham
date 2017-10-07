<?php

namespace Mp\Lib\Package\Auth;

use League\OAuth2\Client\Provider\Google as Provider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

use Mp\Lib\Session as Session;

class AuthGoogle
{

    private $scope = [];

    private $provider = null;

    public function __construct($config = [])
    {
        $this->provider = new Provider($config);
    }

    public function connect($scope = [])
    {
        $authUrl = $this->provider->getAuthorizationUrl([
           'scope' => ['id', 'email', 'first_name', 'last_name', 'gender'],
        ]);

        $provider = 'google';
        $state = $this->provider->getState();
        $redirect = $this->provider->getAuthorizationUrl();

        Session::write('auth.strategy', compact('provider', 'state'));

        return compact('provider', 'state', 'redirect');
    }

    public function callback($option = [])
    {
        try {
            if (Session::check('auth.strategy.token')) {
                $accessToken = unserialize(Session::read('auth.strategy.token'));
                if ($accessToken->hasExpired()) {
                    $accessToken = $this->provider->getAccessToken('refresh_token', [
                        'refresh_token' => $accessToken->getRefreshToken()
                    ]);
                    Session::write('auth.strategy.token', serialize($accessToken));
                }
            } else {
                $accessToken = $this->provider->getAccessToken('authorization_code', [
                    'code' => $option['code']
                ]);
                Session::write('auth.strategy.token', serialize($accessToken));
            }

            $owner = $this
                        ->provider
                        ->getResourceOwner($accessToken)
                        ->toArray();

            if (empty($owner['id'])) {
                return [];
            }

            return [
                'account' => $owner['emails'][0]['value'] ? $owner['emails'][0]['value'] : $owner['id'],
                'email' => $owner['emails'][0]['value'],
                'gender' => 0,
                'fullname' => $owner['displayName'],
                'provider' => 'googl',
                'uid' => $owner['id']
            ];
        } catch (IdentityProviderException $e) {
            print_r("<pre>");
            print_r($e);
            print_r("</pre>");

            return [];
        }
    }
}
