<?php

namespace Mp\Controller\Frontend;

use Mp\App;
use Mp\Lib\Session;
use Mp\Core\Controller\Frontend;

class Cart extends Frontend
{
    public function __construct($model = '', $table = '', $alias = '', $template = '')
    {
        parent::__construct($model, $table, $alias, $template);
    }

    public function navigator()
    {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'add':
                $this->add();
                break;
            case 'delete':
                $this->delete();
                break;
            case 'update':
                $this->update();
                break;
            case 'destroy':
                 $this->destroy();
                break;
            default:
                $this->detail();
                break;
        }
    }

    public function delete()
    {
        $request = App::mp('request');
        $id = $request->query[2];
        $detail = Session::read('cart.detail');

        unset($detail[$id]);

        $total = $this->cartTotal($detail);

        $cart = compact('total', 'detail');

        Session::write('cart', $cart);

        $this->redirect('/cart/detail');
    }

    public function update()
    {
        $request = App::mp('request');

        if (empty($request->data['cart'])) {
            abort('NotFoundException');
        }
        $this->updateCart($request->data['cart'], false);
        // $this->reload(App::mp('request')->referer(), 'detail');
        $this->redirect('/cart/detail');
    }

    public function destroy()
    {
        Session::delete('cart');
        Session::delete('order');
        $this->render('destroy');
    }

    public function detail($id = 0)
    {
        $cart = Session::read('cart');

        $this->render('detail', compact('cart'));
    }

    public function add()
    {
        $request = App::mp('request');

        $id = $request->query[2];

        $option = [
            'select' => 'id, title, price'
        ];
        $target = App::load('product', 'service')->get($id, $option);

        if (empty($target[$id])) {
            abort('NotFoundException');
        }

        $target = $target[$id];
        $target['amount'] = 1;

        $this->updateCart($target);
        $this->back();
    }

    protected function upsertDetail($target = [], &$cart = [])
    {
        $id = $target['id'];
        if (array_key_exists($id, $cart)) {
            $cart[$id]['amount'] += $target['amount'];
        } else {
            $cart[$id] = $target;
        }

        $cart[$id]['sub_total'] = $cart[$id]['price'] * $cart[$id]['amount'];
        $cart[$id]['total'] = $cart[$id]['sub_total'];
    }

    protected function modifyDetail($update = [], &$cart = [])
    {
        foreach ($cart as $id => &$item) {
            if (array_key_exists($id, $update)) {
                $item['amount'] = $update[$id]['amount'];
                $item['sub_total'] = $item['price'] * $item['amount'];
                $item['total'] = $item['sub_total'];
            } else {
                unset($cart[$id]);
            }
        }
    }

    public function updateCart($target = [], $upsert = true)
    {
        $detail = Session::read('cart.detail');
        if (empty($detail)) {
            $detail = [];
        }

        if ($upsert) {
            $this->upsertDetail($target, $detail);
        } else {
            $this->modifyDetail($target, $detail);
        }

        $total = $this->cartTotal($detail);
        $cart = compact('total', 'detail');

        Session::write('cart', $cart);
    }

    public function cartTotal($detail)
    {
        $total = [
            'item' => 0,
            'amount' => 0,
            'sub_total' => 0,
            'total' => 0,
            'shipping' => 0
        ];

        foreach ($detail as $key => $item) {
            $total['item'] ++;
            $total['amount'] += $item['amount'];
            $total['sub_total'] += $item['sub_total'];
            $total['total'] += $item['total'];
        }

        return $total;
    }
}
