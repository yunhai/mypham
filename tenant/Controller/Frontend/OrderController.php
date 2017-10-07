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
            case 'review':
                $this->review();
                break;
            case 'checkout':
                $this->checkout();
                break;
            case 'recipient':
                $this->recipient();
                break;
            case 'deliver':
                $this->deliver();
                break;
            case 'payment':
                $this->payment();
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

    public function review()
    {
        $cart = Session::read('cart');

        if ($cart) {
            $tmp = Hash::combine($cart['detail'], '{n}.id', '{n}.option');
            $tmp = array_filter($tmp);
        }

        $option = empty($tmp) ? false : true;

        $this->render('review', compact('option'));
    }

    public function checkout()
    {
        $login = App::load('login');
        if ($login->loggedIn()) {
            Session::write('order', ['target' => $login->user()]);
            $this->redirect(App::load('url')->full('order/deliver'));
        }
        //'order/recipient'
        $this->redirect(App::load('url')->full('don-hang/thanh-vien-he-thong'));
    }

    public function recipient()
    {
        $request = App::mp('request');

        $cart = Session::read('cart');
        $tmp = Hash::combine($cart['detail'], '{n}.id', '{n}.option');

        $tmp = array_filter($tmp);
        $option = empty($tmp) ? false : true;

        if (empty($request->data)) {
            if (Session::check('order')) {
                //'order/deliver'
                return $this->redirect(App::load('url')->full('don-hang/thong-tin-giao-hang'));
            }

            return $this->render('recipient', compact('option', 'target'));
        }

        $type = $request->data['type'];
        if ($type == 1) {
            $target = [
                'email' => '',
                'fullname' => '',
                'address' => '',
                'phone' => '',
                'type' => 1
            ];
        } else {
            $target = [
                'type' => 2
            ];
            $account = $request->data['login']['account'];
            $password = $request->data['login']['password'];

            $fields = [
                'string_1' => 'address',
                'string_2' => 'phone',
            ];

            $model = App::load('user', 'model');
            $model->extension($fields);
            $flag = App::load('user', 'service', [$model])->login($account, $password);
            if ($flag) {
                $target = array_merge($target, App::load('login')->user());
            } else {
                $error = ['Thông tin đăng nhập chưa chính xác'];

                return $this->render('recipient', compact('error', 'option', 'target'));
            }
        }

        Session::write('order', compact('target'));
        //'order/deliver'
        $this->redirect(App::load('url')->full('don-hang/thong-tin-giao-hang'));
    }

    public function deliver()
    {
        $request = App::mp('request');

        if (Session::check('order.deliver')) {
            $info = Session::read('order.deliver');
        } else {
            $info = Session::read('order.target');
        }

        $freeship = $_SESSION['cart']['total']['sub_total'] >= 500000;

        $location = $province = [];
        if (!$freeship) {
            $model = App::load('location', 'model');
            $location = $model->list();

            $root = key(current($location));
            $province = $location[$root];
        }

        if (empty($request->data)) {
            $cart = Session::read('cart');
            $tmp = Hash::combine($cart['detail'], '{n}.id', '{n}.option');

            $tmp = array_filter($tmp);
            $option = empty($tmp) ? false : true;

            return $this->render('deliver', compact('info', 'option', 'location', 'province', 'freeship'));
        }

        $deliver = $request->data['order'];

        $error = [];
        if (empty($request->data['order']['fullname'])) {
            $error[] = 'Họ tên không được để trống';
        }
        if (empty($request->data['order']['phone'])) {
            $error[] = 'Số điện thoại không được để trống';
        }
        if (empty($request->data['order']['email'])) {
            $error[] = 'Email không hợp lệ';
        }
        if (empty($request->data['order']['address'])) {
            $error[] = 'Địa chỉ không được để trống';
        }
        if (empty($request->data['order']['agreement'])) {
            $error[] = 'Điều khoản sử dụng chưa được đồng ý';
        }
        if (empty($request->data['order']['payment'])) {
            $error[] = 'Phương thức thanh toán chưa được chọn';
        }
        if (!$freeship && empty($request->data['order']['district'])) {
            $error[] = 'Tỉnh thành / quận huyện chưa được chọn';
        }

        if ($error) {
            $info = $deliver;

            return $this->render('deliver', compact('error', 'info', 'option', 'location', 'province', 'freeship'));
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
        //order/payment
        $url = App::load('url')->full('don-hang/gui');
        $this->redirect($url);
    }

    public function payment()
    {
        $cart = Session::read('cart');
        $tmp = Hash::combine($cart['detail'], '{n}.id', '{n}.option');

        $tmp = array_filter($tmp);
        $option = empty($tmp) ? false : true;

        $order = Session::read('order');

        $this->render('payment', compact('option', 'order'));
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

        if (!empty($request->data['subcribe'])) {
            App::load('common')->subcribe($info['order']['target']);
        }

        $this->email($info);
        //'order/finish'

        $url = App::load('url')->full('don-hang/hoan-tat');
        $this->redirect($url);
    }

    public function finish()
    {
        Session::delete('cart');
        Session::delete('order');

        $this->render('finish');
    }

    protected function email($data = [])
    {
        $common = App::load('common');

        if (!empty($data['order']['target']['email'])) {
            $info = [
                'to' => $data['order']['target']['email'],
                'data' => $data['order']['target']
            ];
        } elseif (!empty($data['order']['deliver']['email'])) {
            $info = [
                'to' => $data['order']['deliver']['email'],
                'data' => $data['order']['deliver']
            ];
        }

        // to admin
        $common->sendEmail('22001', $data, $info);

        // to client
        //unset($info['to']);
        $common->sendEmail('22002', $data, $info);
    }

    protected function save($cart = [], &$code = '')
    {
        $userId = App::load('login')->userId() ?? 0;
        $order = [
            'user_id' => $userId,
            'recipient' => json_encode($this->recipientInfo($cart), true),
            'sub_total' => $cart['total']['sub_total'],
            'total' => $cart['total']['total'],
            'tax' => $cart['total']['shipping'],
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
        $cart->extension();

        $data = [];
        $lastId = [];
        foreach ($detail as $id => $item) {
            $option = empty($item['selected-option']) ? '' : $item['selected-option'];
            $sub_total = $item['price'] * $item['price'];
            $data = [
                'order_id' => $orderId,
                'option' => $option,
                'price' => $item['price'],
                'quantity' => $item['amount'],
                'sub_total' => $item['sub_total'],
                'total' => $item['total'],
                'tax' => empty($item['shipping']) ? 0 : $item['shipping']  // luu tam cho shipping
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

    protected function recipientInfo($cart = [])
    {
        return $info = Session::read('order.deliver');

        return array_merge($info, ['shipping' => $cart['total']['shipping']]);
    }
}
