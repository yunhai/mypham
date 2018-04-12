<?php

use Mp\App;
use Mp\Lib\Session;
use Mp\Lib\Utility\Hash;
use Mp\Core\Controller\Frontend;

class UserController extends Frontend
{
    public function __construct($model = 'user', $table = 'user', $alias = 'user', $template = '')
    {
        parent::__construct($model, $table, $alias, $template);
    }

    public function navigator()
    {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'logout':
                $this->logout();
                break;
            case 'login':
                $this->login();
                break;
            case 'sign-up':
                $this->signUp();
                break;
            case 'update-password':
                $this->updatePassword();
                break;
            case 'forget-password':
                $this->forgetPassword();
                break;
            case 'reset-password':
                $this->resetPassword();
                break;
            case 'verify':
                $this->verify();
                break;
            case 'me':
                $this->me();
                break;
            case 'balance':
                $this->balance();
                break;
            default:
                break;
        }
    }

    public function updatePassword()
    {
        $request = App::mp('request');

        if (!empty($request->data)) {
            $flag = $this->makeUpdatePassword($request->data);
            if ($flag) {
                return $this->render('update-password-finish');
            }
        }

        $breadcrumb = [
            ['title' => 'Thay đổi mật khẩu']
        ];
        $this->set('breadcrumb', $breadcrumb);

        return $this->render('update-password');
    }

    public function makeUpdatePassword($data = [])
    {
        $request = App::mp('request');
        $channel = array_search($request->channel, App::mp('config')->get('app.channel'));

        $data['id'] = App::load('login')->userId();

        $error = [];
        $flag = App::load('user', 'service', [$this->model()])->updatePassword($data, $error, true, 'updatePassword');

        if (!$flag) {
            $this->set('error', $error);

            return false;
        }

        return true;
    }

    public function balance()
    {
        $id = App::load('login')->userId();
        $option = [
            'select' => 'id',
            'where' => 'id = ' . $id
        ];

        $data = $this->model()->find($option, 'first');
        $data = current($data);

        $breadcrumb = [
            ['title' => 'Thông tin điểm thưởng']
        ];
        $this->set('breadcrumb', $breadcrumb);

        $this->render('balance', compact('data'));
    }

    public function me()
    {
        $request = App::mp('request');

        $id = App::load('login')->userId();

        $option = [
            'select' => 'id, email, fullname, account',
            'where' => 'id = ' . $id
        ];

        $data = $this->model()->find($option, 'first');
        $data = current($data);

        if (!empty($request->data)) {
            $data = array_merge($data, $request->data);
            $data['id'] = $id;
            $this->makeMe($data);
        }

        $breadcrumb = [
            ['title' => 'Thông tin tài khoản']
        ];
        $this->set('breadcrumb', $breadcrumb);


        $this->render('me', compact('data'));
    }

    private function makeMe($data = [])
    {
        $request = App::mp('request');
        $channel = array_search($request->channel, App::mp('config')->get('app.channel'));

        $data['channel'] = $channel;
        $data['group_id'] = current(array_flip(App::load('group', 'model')->base()));

        $error = [];
        $flag = $this->validate($this->model()->alias(), $data, $error, 1, 'signUp');

        if (!$flag) {
            $this->set('error', $error);

            return false;
        }

        return $this->model()->save($data);
    }

    public function resetPassword()
    {
        $request = App::mp('request');
        $token = mb_substr($request->get()['request'], 20);

        $token = $this->extractToken($token);

        $breadcrumb = [
            ['title' => 'Cập nhật mật khẩu']
        ];
        $this->set('breadcrumb', $breadcrumb);

        if (empty($request->data)) {
            if ($token) {
                return $this->render('reset-password');
            }
            abort('NotFoundException');
        }

        return $this->makeResetPassword($token);
    }

    public function makeResetPassword($token)
    {
        $request = App::mp('request');

        if ($token) {
            $error = [];
            $data = $request->data;
            if ($token['email'] !== $data['email']) {
                $error = [
                    'email' => [
                        'Địa chỉ email không chính xác'
                    ]
                ];
            } else {
                $target = $this->model()->get($data['email']);
                $target = $target[$this->model()->alias()];
                $data = array_merge($target, $data);

                $flag = App::load('user', 'service', [$this->model()])->updatePassword($data, $error, true, 'resetPassword');

                if ($flag) {
                    return $this->render('reset-password-finish');
                }
            }

            return $this->render('reset-password', compact('error', 'data'));
        }

        abort('NotFoundException');
    }

    protected function email($template = '', $data = [], $mail = [], $priority = 30)
    {
        $common = App::load('common');
        return $common->sendEmail($template, $data, $mail);
    }

    public function forgetPassword()
    {
        $request = App::mp('request');

        if (isset($request->data)) {
            $target = $this->model()->get($request->data['email']);

            if ($target) {
                $target = $this->master($target, $this->model()->alias());

                $token = [
                    'email' => $target['email'],
                    'expire' => dechex(strtotime('+1 hour', time())),
                ];

                $token = $this->generateToken($token);

                $target['url'] = App::load('url')->module('reset-password') . '/' . $token;

                $info = [
                    'to' => $target['email']
                ];
                $this->email('m1002', $target, $info);

                return $this->render('forget-password-finish');
            }
        }

        $breadcrumb = [
            ['title' => 'Khôi phục mật khẩu']
        ];
        $this->set('breadcrumb', $breadcrumb);

        $this->render('forget-password');
    }

    public function signUp()
    {
        $request = App::mp('request');

        if (App::load('login')->loggedIn()) {
            return $this->redirect(App::load('url')->module('me'));
        }

        $breadcrumb = [
            ['title' => 'Đăng ký thành viên']
        ];
        $this->set('breadcrumb', $breadcrumb);

        if (isset($request->data)) {
            $flag = $this->makeSignUp($request->data);
            if ($flag) {
                return $this->render('sign-up-finish');
            }

            $this->set('data', $request->data);
        }

        $this->render('sign-up');
    }

    private function makeSignUp($data = [])
    {
        $request = App::mp('request');

        $data['status'] = 0;
        $data['channel'] = $request->branch();
        $data['group_id'] = current(array_flip(App::load('group', 'model')->base()));

        $error = [];
        $flag = $this->validate($this->model()->alias(), $data, $error, 1, 'signUp');

        if (!$flag) {
            $this->set('error', $error);

            return false;
        }

        $data['account'] = $data['email'];
        $data['password'] = App::load('user', 'service', [$this->model()])->encryptPassword($data['password']);

        $flag = $this->model()->save($data);

        if (!$flag) {
            return false;
        }

        $id = $this->model()->lastInsertId();
        $saved = [
            'creator' => $id,
            'editor' => $id,
            'modified' => 'NOW()'
        ];

        $this->model()->forceModify($saved, 'id = ' . $id);
        if (!empty($data['subcribe'])) {
            App::load('common')->subcribe($data);
        }

        $token = [
            'id' => $this->model()->lastInsertId(),
            'email' => $data['account'],
            'provider' => 'local',
            'expire' => dechex(strtotime('24 hour', time()))
        ];

        $token = $this->generateToken($token);
        $data['url'] = App::load('url')->module('verify') . '/' . $token;

        $info = [
            'to' => $data['email']
        ];

        $data['password'] = $data['confirm-password'];
        $this->email('m1001', $data, $info, 1);

        return true;
    }

    public function verify()
    {
        $request = App::mp('request');

        $token = mb_substr($request->get()['request'], 12);

        $token = $this->extractToken($token);

        $breadcrumb = [
            ['title' => 'Xác thực tài khoản']
        ];
        $this->set('breadcrumb', $breadcrumb);

        if ($token) {
            $alias = $this->model()->alias();
            $target = $this->model()->id($token['id'], "{$alias}.id, {$alias}.email, {$alias}.provider", 1);

            $target = $target[$alias];
            if ($target['email'] == $token['email'] && $target['provider'] == $token['provider']) {
                $this->model()->modify(['status' => 1], 'id = ' . $token['id']);

                return $this->render('verify');
            }
        }
        abort('NotFoundException');
    }

    public function extractToken($token)
    {
        $token = trim($token, '/');
        if ($token) {
            $api = $this->appApi();

            $security = new \Mp\Lib\Helper\Security();
            $token = $security->decrypt($token, $api, 2);

            if ($token) {
                $params = [];
                foreach (explode('&', $token) as $item) {
                    list($key, $value) = explode('=', $item);
                    $params[$key] = $value;
                }

                if ($params && dechex(time()) <= $params['expire']) {
                    return $params;
                }
            }
        }

        return [];
    }

    private function generateToken($data = [])
    {
        $token = [];
        foreach ($data as $key => $value) {
            $token[] = $key . '=' . $value;
        }

        $token = implode($token, '&');
        $security = new \Mp\Lib\Helper\Security();

        $api = $this->appApi();

        return $security->encrypt($token, $api, 2);
    }

    private function appApi()
    {
        $model = new \Mp\Model\Apps();

        return $model->api(App::load('login')->targetId());
    }

    public function conf()
    {
        $helper = App::mp('config');

        $file = [
            ROOT . 'config' . DS . 'auth'
        ];

        $conf = App::mp('config')->load($file);
        $security = new \Mp\Lib\Helper\Security();
        $conf['security_salt'] = $security->random(64);

        return $conf;
    }

    protected function _login($strategy = 'local')
    {
        if ($strategy == 'facebook') {
            return $this->_facebookLogin();
        }
        if ($strategy == 'google') {
            return $this->_googleLogin();
        }

        return $this->_localLogin();
    }

    protected function _localLogin()
    {
        $request = App::mp('request');

        $account = $request->data['email'];
        $password = $request->data['password'];

        return App::load('user', 'service', [$this->model()])->login($account, $password);
    }

    protected function _facebookLogin()
    {
        $helper = new \Mp\Lib\Helper\Url();
        $request = App::mp('request');
        $config = $this->conf();

        $config = [
            'clientId' => $config['facebook']['id'],
            'clientSecret' => $config['facebook']['token'],
            'redirectUri' => $helper->full($config['facebook']['redirect']),
        ];

        $auth = new \Mp\Lib\Package\Auth\AuthFacebook($config);
        $query = $request->param;

        if (Session::check('auth.strategy.state') && isset($query['code'])) {
            $data = $auth->callback($query);
            if ($data) {
                Session::delete('auth.strategy');
                $flag = $this->__externalLogin($data, 'facebook');
                if ($flag) {
                    return $this->redirect($helper->full());
                }
            }

            return $this->redirect('/user/login');
        }

        $result = $auth->connect();

        return $this->redirect($result['redirect']);
    }

    protected function _googleLogin()
    {
        $helper = new \Mp\Lib\Helper\Url();
        $request = App::mp('request');
        $config = $this->conf();

        $config = [
            'clientId' => $config['google']['id'],
            'clientSecret' => $config['google']['token'],
            'redirectUri' => $helper->full($config['google']['redirect']),
        ];

        $auth = new \Mp\Lib\Package\Auth\AuthGoogle($config);
        $query = $request->param;

        if (Session::check('auth.strategy.state') && isset($query['code'])) {
            $data = $auth->callback($query);

            if ($data) {
                Session::delete('auth.strategy');
                $flag = $this->__externalLogin($data, 'google');
                if ($flag) {
                    return $this->redirect($helper->full());
                }
            }

            return $this->redirect('/user/login');
        }

        $result = $auth->connect();

        return $this->redirect($result['redirect']);
    }

    private function __externalLogin($data = [], $provider = 'facebook')
    {
        $alias = $this->model()->alias();
        $fields = "{$alias}.id, {$alias}.fullname, {$alias}.email";

        $info = $this->model()->externalLogin($data['uid'], $provider, $fields);

        if (empty($info)) {
            $security = new \Mp\Lib\Helper\Security();
            $password = $security->random(8, 5);
            $data['password'] = $password;
            $data['provider'] = $provider;

            $flag = $this->authSignUp($data);

            if (!$flag) {
                return false;
            }
            $info = $this->model()->externalLogin($data['uid'], $provider, $fields);
        }

        $auth = new \Mp\Lib\Package\Auth\Auth(null);
        $auth->storeLoginInfo($info);

        return true;
    }

    private function authSignUp($data = [])
    {
        $request = App::mp('request');

        $data['status'] = 1;
        $data['channel'] = $request->branch();
        $data['group_id'] = current(array_flip(App::load('group', 'model')->base()));
        $data['password'] = App::load('user', 'service', [$this->model()])->encryptPassword($data['password']);
        $this->model()->extension();
        $flag = $this->model()->save($data);

        if (!$flag) {
            return false;
        }

        $id = $this->model()->lastInsertId();
        $saved = [
            'creator' => $id,
            'editor' => $id,
            'modified' => 'NOW()'
        ];

        $this->model()->forceModify($saved, 'id = ' . $id);

        return true;
    }

    public function login()
    {
        $request = App::mp('request');

        $strategy = isset($request->query[2]) ? $request->query[2] : 'local';

        if ($strategy == 'local' && empty($request->data)) {
            return $this->render('login');
        }
        if ($strategy) {
            $flag = $this->_login($strategy);

            if ($flag) {
                $this->redirect(App::load('url')->full());
            } elseif ($strategy == 'local') {
                $this->set('error', ['Thông tin login chưa chính xác']);
            }
        }

        $this->render('login');
    }

    public function logout()
    {
        App::load('user', 'service', [$this->model()])->logout();
        $this->redirect(App::load('url')->full());
    }
}
