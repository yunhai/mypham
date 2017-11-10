<?php

use Mp\App;

use Mp\Lib\Session;
use Mp\Lib\Utility\Hash;
use Mp\Controller\Frontend\Order;

class OrderController extends Order
{
    public function navigator()
    {
        $request = App::mp('request');
        if (!Session::check('cart')) {
            $this->redirect(App::load('url')->full('/'));
        }

        switch ($request->query['action']) {
            case 'deliver':
                $this->deliver();
                break;
            case 'go':
                $this->go();
                break;



            // case 'recipient':
            //     $this->recipient();
            //     break;
            // case 'checkout':
            //     $this->checkout();
            //     break;
            //
            // case 'payment':
            //     $this->payment();
            //     break;
            // case 'go':
            //     $this->go();
            //     break;
            case 'finish':
                $this->finish();
                break;
            case 'history':
                $this->history();
                break;
            default:
                $this->detail($request->query[2]);
                break;
        }
    }

    public function deliver()
    {
        $request = App::mp('request');

        $info = [];
        if (Session::check('order.deliver')) {
            $info = Session::read('order.deliver');
        }

        $config = App::mp('config');

        $freeship = $config->get('freeship');
        $freeship = ($_SESSION['cart']['total']['sub_total'] >= $freeship);

        $location = $province = [];
        if (!$freeship) {
            $model = App::load('location', 'model');
            $location = $model->list();

            $root = key(current($location));
            $province = $location[$root];
        }

        if (empty($request->data)) {
            $cart = Session::read('cart');

            $login = App::load('login');
            if ($login->loggedIn()) {
                $info = $login->user();
                $user = [
                    'buyer_fullname' => $info['fullname'],
                    'buyer_phone' => $info['phone'],
                    'buyer_email' => $info['email'],
                    'buyer_address' => $info['address'],
                ];
                $info = array_merge($info, $user);
            }

            return $this->render('deliver', compact('info', 'location', 'province', 'freeship'));
        }

        $deliver = $request->data['order'];

        $error = $this->validateDelivery($request->data, $freeship);
        if ($error) {
            $info = $deliver;

            return $this->render('deliver', compact('error', 'info', 'location', 'province', 'freeship'));
        }

        if (!$freeship) {
            $tmp = $location[$deliver['province']] ? $location[$deliver['province']] : [];
            $tmp = $tmp ? $tmp[$deliver['district']] : [];
            $shipping = 0;
            if ($tmp) {
                $shipping = $tmp['delivery_price'];
            }

            $_SESSION['cart']['total']['shipping'] = $shipping;
            $_SESSION['cart']['total']['total'] += $shipping;

            $address1 = $province[$deliver['province']]['title'] ?? '';
            $address2 = $location[$deliver['province']][$deliver['district']]['title'] ?? '';

            $deliver['address'] .= " ({$address2}, {$address1})";
        }

        Session::write('order.deliver', $deliver);

        $url = App::load('url')->full('order/go');
        $this->redirect($url);
    }

    public function go()
    {
        $request = App::mp('request');

        $cart = Session::read('cart');

        $order_code = '';
        $this->save($cart, $order_code);

        $info = [
            'order' => Session::read('order'),
            'cart' => $cart,
            'code' => $order_code
        ];

        if ($info['order']['deliver']['payment'] == 2) {
            $info['payment_text'] = 'Chuyển khoản ngân hàng';
        } else {
            $info['payment_text'] = 'Thanh toán khi nhận hàng';
        }

        $this->email($info);
    
        $url = App::load('url')->full('order/finish');
        $this->redirect($url);
    }

    protected function email($data = [])
    {
        $common = App::load('common');
        $config = App::mp('config');

        $info = [
            'bcc' => $config->app['root']['email']
        ];
        $client_email = $data['order']['deliver']['buyer_email'];

        $app = $config->app['app'];
        $admin_email = $app['email'];

        $basic = [
            'app' => [
                'domain' => $app['url']['domain'],
                'name' => $app['title'],
            ]
        ];
        $data = array_merge($data, $basic);

        // to admin
        $info['to'] = $admin_email;
        $common->sendEmail('m2001', $data, $info);

        // to client
        $info['to'] = $client_email;
        $common->sendEmail('m2002', $data, $info);
    }

    public function finish()
    {
        Session::delete('cart');
        Session::delete('order');

        $this->render('finish');
    }

    protected function save($cart = [], &$code = '')
    {
        $recipient = Session::read('order.deliver');
        $userId = App::load('login')->userId() ?? 0;
        $order = [
            'user_id' => $userId,
            'recipient' => json_encode($recipient, true),
            'sub_total' => $cart['total']['sub_total'],
            'total' => $cart['total']['total'],
            'shipping' => $cart['total']['shipping'] ?? 0,
            'status' => 0,
            'note' => Session::read('order.deliver.note')
        ];
        $flag = $this->model()->save($order);
        if (!$flag) {
            return false;
        }

        $detail = $cart['detail'];
        $orderId = $this->model()->lastInsertId();

        $cart = $this->model()->cart;

        $data = [];
        $lastId = [];
        foreach ($detail as $id => $item) {
            $sub_total = $item['price'] * $item['price'];
            $data = [
                'order_id' => $orderId,
                'price' => $item['price'],
                'quantity' => $item['amount'],
                'sub_total' => $item['sub_total'],
                'total' => $item['total'],
                'title' => $item['title'] ?? '',
                'property_text' => $item['property_text'] ?? '',
                'property_detail_text' => $item['property_detail_text'] ?? ''
            ];

            $cart->save($data);

            $modify = [
                'target_id' => $item['id'],
                'target_model' => $item['model']
            ];
            $this->model()->cart->modifyPk($modify, 'id = ' . $cart->lastInsertId());
        }

        $code = 'ORD' . (1000 + $orderId);
        $this->model()->modify(compact('code'), 'id = ' . $orderId);

        return true;
    }

    protected function validateDelivery($data = [], $freeship = false)
    {
        $error = [];

        if (empty($data['order']['buyer_fullname'])) {
            $error['buyer_fullname'] = 'Họ tên không được để trống';
        }
        if (empty($data['order']['buyer_phone'])) {
            $error['buyer_phone'] = 'Số điện thoại không được để trống';
        }
        if (empty($data['order']['buyer_email'])) {
            $error['buyer_email'] = 'Email không hợp lệ';
        }
        if (empty($data['order']['fullname'])) {
            $error['fullname'] = 'Họ tên không được để trống';
        }
        if (empty($data['order']['phone'])) {
            $error['phone'] = 'Số điện thoại không được để trống';
        }
        if (empty($data['order']['email'])) {
            $error['email'] = 'Email không hợp lệ';
        }
        if (empty($data['order']['address'])) {
            $error['address'] = 'Địa chỉ không được để trống';
        }
        if (empty($data['order']['payment'])) {
            $error['payment'] = 'Phương thức thanh toán chưa được chọn';
        }
        if (!$freeship && empty($data['order']['district'])) {
            $error['district'] = 'Tỉnh thành / quận huyện chưa được chọn';
        }

        return $error;
    }

    ///////////////////////////////////////////////////////////
    public function history()
    {
        $request = App::mp('request');
        $login = App::load('login');
        if ($login->loggedIn()) {
            $option = [
                'select' => 'id, code, total, status, created, modified',
                'where' => 'user_id = ' . $login->userId(),
                'order' => 'id desc',
                'limit' => 10,
                'page' => empty($request->name['page']) ? 1 : $request->name['page'],
                'paginator' => [
                    'navigator' => false
                ]
            ];

            $data = $this->paginate($option);

            if ($data['list']) {
                $data['list'] = Hash::combine($data['list'], '{n}.order.id', '{n}.order');
                $this->set('status', $this->status($this->model()->alias()));
            }

            $this->set('data', $data);
        }

        $this->render('history');
    }

    public function detail($id = 0)
    {
        $id = (int) $id;

        $alias = $this->model()->alias();

        $fields = "{$alias}.id, {$alias}.user_id, {$alias}.code, {$alias}.total, {$alias}.tax, {$alias}.sub_total, {$alias}.status, {$alias}.recipient, {$alias}.note, {$alias}.modified, {$alias}.created";

        $target = $this->model()->findById($id, $fields);
        if (empty($target)) {
            abort('NotFoundException');
        }

        if ($target['order']['recipient']) {
            $target['order']['recipient'] = json_decode($target['order']['recipient']);
        }

        $this->model()->attactCart();

        $target = $target[$alias];
        $target['detail'] = $this->model()->cart($id);

        $this->associate(Hash::combine($target['detail'], '{n}.target_id', '{n}.target'));
        $status = $this->status($alias);

        return $this->render('detail', compact('target', 'status'));
    }

    public function status($alias = '')
    {
        $status = App::mp('config')->get('status');

        if (empty($status[$alias])) {
            return $status['default'];
        }

        return $status[$alias];
    }

    // public function checkout()
    // {
    //     $login = App::load('login');
    //     if ($login->loggedIn()) {
    //         Session::write('order', ['target' => $login->user()]);
    //         $this->redirect(App::load('url')->full('order/deliver'));
    //     }
    //     //'order/recipient'
    //     $this->redirect(App::load('url')->full('don-hang/thanh-vien-he-thong'));
    // }
    //
    // public function recipient()
    // {
    //     $request = App::mp('request');
    //
    //     $cart = Session::read('cart');
    //     $tmp = Hash::combine($cart['detail'], '{n}.id', '{n}.option');
    //
    //     $tmp = array_filter($tmp);
    //     $option = empty($tmp) ? false : true;
    //
    //     if (empty($request->data)) {
    //         if (Session::check('order')) {
    //             return $this->redirect(App::load('url')->full('order/deliver'));
    //         }
    //
    //         return $this->render('recipient', compact('option', 'target'));
    //     }
    //
    //     $type = $request->data['type'];
    //     if ($type == 1) {
    //         $target = [
    //             'email' => '',
    //             'fullname' => '',
    //             'address' => '',
    //             'phone' => '',
    //             'type' => 1
    //         ];
    //     } else {
    //         $target = [
    //             'type' => 2
    //         ];
    //         $account = $request->data['login']['account'];
    //         $password = $request->data['login']['password'];
    //
    //         $fields = [
    //             'string_1' => 'address',
    //             'string_2' => 'phone',
    //         ];
    //
    //         $model = App::load('user', 'model');
    //         $model->extension($fields);
    //         $flag = App::load('user', 'service', [$model])->login($account, $password);
    //         if ($flag) {
    //             $target = array_merge($target, App::load('login')->user());
    //         } else {
    //             $error = ['Thông tin đăng nhập chưa chính xác'];
    //
    //             return $this->render('membership', compact('error', 'option', 'target'));
    //         }
    //     }
    //
    //     Session::write('order', compact('target'));
    //     $this->redirect(App::load('url')->full('order/deliver'));
    // }

    // public function go()
    // {
    //     $request = App::mp('request');
    //
    //     $cart = Session::read('cart');
    //
    //     $order_code = '';
    //     $this->save($cart, $order_code);
    //
    //     $info = [
    //         'order' => Session::read('order'),
    //         'cart' => $cart,
    //         'code' => $order_code
    //     ];
    //
    //     if ($info['order']['deliver']['payment'] == 2) {
    //         $info['payment_text'] = 'Chuyển khoản ngân hàng';
    //     } else {
    //         $info['payment_text'] = 'Thanh toán khi nhận hàng';
    //     }
    //
    //     if (!empty($request->data['subcribe'])) {
    //         App::load('common')->subcribe($info['order']['target']);
    //     }
    //
    //     $this->email($info);
    //     //'order/finish'
    //
    //     $url = App::load('url')->full('don-hang/hoan-tat');
    //     $this->redirect($url);
    // }
}
