<?php

namespace Mp\Lib\Package\Auth;

use League\OAuth2\Client\Provider\Facebook as Provider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

use Mp\Lib\Session as Session;

class AuthFacebook
{
    private $scope = [];
    private $version = 'v2.9';
    private $provider = null;

    public function __construct($config = [])
    {
        $config = array_merge($config, ['graphApiVersion' => $this->version]);
        $this->provider = new Provider($config);
    }

    public function connect($scope = [])
    {
        $provider = 'fb';

        $redirect = $this->provider->getAuthorizationUrl();
        $state = $this->provider->getState();

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
                    'code' =>$_GET['code']
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
                'account' => $owner['email'] ? $owner['email'] : $owner['id'],
                'email' => $owner['email'],
                'gender' => strtolower($owner['gender']) == 'male' ? 1 : 0,
                'fullname' => $owner['first_name'] . ' ' . $owner['last_name'],
                'uid' => $owner['id']
            ];
        } catch (IdentityProviderException $e) {
            // print_r("<pre>Error;");
            // print_r($e);
            // print_r("</pre>");
            //         exit;
            return [];
        }
    }
}
