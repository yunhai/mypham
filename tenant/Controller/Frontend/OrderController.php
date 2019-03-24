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

        $ignore = [
            'history', 'detail'
        ];
        if (!in_array($request->query['action'], $ignore) && !Session::check('cart')) {
            $this->redirect(App::load('url')->full('/'));
        }

        switch ($request->query['action']) {
            case 'deliver':
                $this->deliver();
                break;
            case 'go':
                $this->go();
                break;
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

        $model = App::load('location', 'model');
        $location = $model->list();

        $root = key(current($location));
        $province = $location[$root];

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

        if (empty($deliver['buyer_seperate'])) {
            $deliver['buyer_fullname'] = $deliver['fullname'];
            $deliver['buyer_phone'] = $deliver['phone'];
            $deliver['buyer_email'] = $deliver['email'];
        }

        $error = $this->validateDelivery($deliver, $freeship);
        if ($error) {
            $info = $deliver;

            return $this->render('deliver', compact('error', 'info', 'location', 'province', 'freeship'));
        }

        $shipping = 0;
        $delivery_day = '';
        if (!$freeship) {
            $tmp = $location[$deliver['province']] ? $location[$deliver['province']] : [];
            $tmp = $tmp ? $tmp[$deliver['district']] : [];

            if ($tmp) {
                $shipping = $tmp['delivery_price'];
                $delivery_day = $tmp['delivery_day'];
            }

            $_SESSION['cart']['total']['shipping'] = $shipping;
            $_SESSION['cart']['total']['total'] += $shipping;

            $address1 = $province[$deliver['province']]['title'] ?? '';
            $address2 = $location[$deliver['province']][$deliver['district']]['title'] ?? '';

            $deliver['address'] .= " ({$address2}, {$address1})";
        }

        $deliver['shipping'] = $shipping;
        $deliver['delivery_day'] = $delivery_day;
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
        Session::write('order.code', $order_code);

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
        $order = Session::read('order');
        Session::delete('cart');
        Session::delete('order');

        $this->render('finish', compact('order'));
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
            'delivery_day' => $recipient['delivery_day'] ?? '0',
            'status' => 0,
            'note' => $recipient['note']
        ];
        $flag = $this->model()->save($order);
        if (!$flag) {
            return false;
        }

        $detail = $cart['detail'];
        $orderId = $this->model()->lastInsertId();

        $model = $this->model()->cart;
        
        $data = [];
        $lastId = [];
        $update = [];
        foreach ($detail as $id => $item) {
            $sub_total = $item['price'] * $item['price'];
            $data = [
                'order_id' => $orderId,
                'price' => $item['price'],
                'quantity' => $item['amount'],
                'sub_total' => $item['sub_total'],
                'total' => $item['total'],
                'title' => $item['title'] ?? '',
                'property_id' => $item['property_id'] ?? '',
                'property_text' => $item['property_text'] ?? '',
                'property_log' => empty($item['property_log']) ? '' : json_encode($item['property_log']),
            ];
            
            $this->model()->cart->save($data);
            array_push($update, [
                'id' => $this->model()->cart->lastInsertId(),
                'target_id' => $item['id'],
                'target_model' => $item['model']
            ]);
        }

        $this->model()->cart->removeExtension();
        foreach ($update as $target) {
            $this->model()->cart->modifyPk($target, 'id = ' . $target['id']);
        }

        $code = 'ORD' . (1000 + $orderId);
        $this->model()->removeExtension();
        $this->model()->modify(compact('code'), 'id = ' . $orderId);

        return true;
    }

    protected function validateDelivery($data = [], $freeship = false)
    {
        $error = [];

        if (empty($data['buyer_fullname'])) {
            $error['buyer_fullname'] = 'Họ tên không được để trống';
        }
        if (empty($data['buyer_phone'])) {
            $error['buyer_phone'] = 'Số điện thoại không được để trống';
        }
        if (empty($data['buyer_email'])) {
            $error['buyer_email'] = 'Email không hợp lệ';
        }
        if (empty($data['fullname'])) {
            $error['fullname'] = 'Họ tên không được để trống';
        }
        if (empty($data['phone'])) {
            $error['phone'] = 'Số điện thoại không được để trống';
        }
        if (empty($data['email'])) {
            $error['email'] = 'Email không hợp lệ';
        }
        if (empty($data['address'])) {
            $error['address'] = 'Địa chỉ không được để trống';
        }
        if (empty($data['payment'])) {
            $error['payment'] = 'Phương thức thanh toán chưa được chọn';
        }
        if (!$freeship && empty($data['district'])) {
            $error['district'] = 'Tỉnh thành / quận huyện chưa được chọn';
        }

        return $error;
    }

    public function history()
    {
        $request = App::mp('request');
        $login = App::load('login');

        if (!$login->loggedIn()) {
            $url = App::load('url')->full('user/login');
            $this->redirect($url);
        }

        $breadcrumb = [
            ['title' => 'Lịch sử giao dịch']
        ];
        $this->set('breadcrumb', $breadcrumb);

        $option = [
            'select' => 'id, code, total, status, created, modified',
            'where' => 'user_id = ' . $login->userId(),
            'order' => 'id desc',
            'limit' => 1000,
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

        $this->render('history');
    }

    public function detail($code = 0)
    {
        $alias = $this->model()->alias();
        $login = App::load('login');

        if (!$login->loggedIn()) {
            $url = App::load('url')->full('user/login');
            $this->redirect($url);
        }

        $fields = "{$alias}.id, {$alias}.user_id, {$alias}.code, {$alias}.total, {$alias}.tax, {$alias}.sub_total, {$alias}.status, {$alias}.recipient, {$alias}.note, {$alias}.modified, {$alias}.created";
        $option = [
            'select' => $fields,
            'where' => 'user_id = ' . $login->userId() . ' AND ' . $alias .'.code = "' . $code . '"',
            'limit' => 1
        ];

        $target = $this->model()->find($option, 'first');

        if (empty($target)) {
            abort('NotFoundException');
        }

        if ($target['order']['recipient']) {
            $target['order']['recipient'] = json_decode($target['order']['recipient']);
        }

        $this->model()->attactCart();

        $target = $target[$alias];

        $order_id = $target['id'];
        $target['detail'] = $this->model()->cart($order_id);

        $this->associate(Hash::combine($target['detail'], '{n}.target_id', '{n}.target'));
        $status = $this->status($alias);

        $breadcrumb = [
            ['title' => 'Lịch sử giao dịch', 'url' => 'order/history'],
            ['title' => 'Tra cứu đơn hàng']
        ];
        $this->set('breadcrumb', $breadcrumb);

        return $this->render('detail', compact('target', 'status'));
    }

    private function status($alias = '')
    {
        $status = App::mp('config')->get('status');

        if (empty($status[$alias])) {
            return $status['default'];
        }

        return $status[$alias];
    }
}
