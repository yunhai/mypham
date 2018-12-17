<?php

use Mp\App;
use Mp\Lib\Session;
use Mp\Lib\Utility\Hash;
use Mp\Controller\Frontend\Cart;

class CartController extends Cart
{
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

    public function detail($id = 0)
    {
        $cart = Session::read('cart');
        $product = Session::read('product_lists');
        $this->associate($product);
        $this->render('detail', compact('cart', 'product'));
    }

    public function add()
    {
        $request = App::mp('request');
        
        $id = $request->query[2];
        $target = $this->getTarget($id);

        $price = $target['final_price'];

        if (isset($request->data)) {
            $amount = $request->data['amount'];
            $property_id = isset($request->data['property']) ? $request->data['property'] : '';
        } else {
            $amount = 1;
            $property_id = '';
            if (!empty($target['property'])) {
                reset($target['property']);
                $property_id = key($target['property']);
            }
        }
        
        $option = [];
        if (isset($target['property'][$property_id])) {
            $property = $target['property'][$property_id];
            $price = $property['final_price'];
            $option = [
                'property_id' => $property['id'],
                'property_text' => $property['title'],
                'property_log' => $property,
            ];
        }

        $cart = [
            'id' => $target['id'],
            'title' => $target['title'],
            'amount' => $amount,
            'price' => $price,
            'model' => 'product',
        ];

        if ($option) {
            $cart = array_merge($cart, $option);
        }
    
        $this->updateCart($cart);

        if (empty($product_lists[$id])) {
            $product_lists[$id] = $target;
            Session::write('product_lists', $product_lists);
        }

        if (empty($request->data['buynow'])) {
            return $this->back();
        }

        $this->redirect('/order/deliver');
    }

    private function getTarget($id)
    {
        $product_lists = Session::read('product_lists');
        $product_lists = [];
        if (empty($product_lists[$id])) {
            $extends = [
                'string_1' => 'promote',
                'string_2' => 'store_id',
                'string_3' => 'manufacturer_id',
                'string_4' => 'promote_start',
                'string_5' => 'promote_end',
                'text_1' => [
                    'property',
                    'display_mode',
                    'default_mode',
                    'code',
                    'gallery',
                    'files'
                ]
            ];

            $option = [
                'select' => 'id, title, price, file_id',
                'where' => 'id = '. $id
            ];

            $target = App::load('product', 'service')->get($option, $extends);

            if (empty($target[$id])) {
                abort('NotFoundException');
            }

            return $target[$id];
        }
        return $product_lists[$id];
    }
}
