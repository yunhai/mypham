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
        $product_lists = Session::read('product_lists');

        if (empty($product_lists[$id])) {
            $extends = [
                'string_1' => 'promote',
                'string_2' => 'store_id',
                'string_3' => 'manufacturer',
                'string_4' => 'promote_start',
                'string_5' => 'promote_end',
                'text_1' => [
                    'point',
                    'property',
                    'property_name',
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

            $target = $target[$id];
        } else {
            $target = $product_lists[$id];
        }

        $price = $target['price'];
        $target['is_promotion'] = false;
        if ($target['is_promotion']) {
            $price = $target['promote'];
        }

        $cart = ['model' => 'product'];

        if (isset($request->data)) {
            $amount = $request->data['amount'];
            $property = isset($request->data['property']) ? $request->data['property'] : '';
            $property_detail = isset($request->data['property_detail']) ? $request->data['property_detail'] : '';

            $property_text = '';
            $property_detail_text = '';

            if (isset($target['property'][$property])) {
                $property_text = $target['property'][$property]['title'];
                if ($target['property'][$property]['detail']) {
                    $property_detail_text = $target['property'][$property]['detail'][$property_detail]['title'];
                }

                if ($target['property'][$property]['price']) {
                    if ($target['is_promotion']) {
                        $price = $target['property'][$property]['price_promote'];
                    } else {
                        $price = $target['property'][$property]['price'];
                    }
                }
            }
        } else {
            $amount = 1;
            $property = '';
            $property_text = '';
            $property_detail = '';
            $property_detail_text = '';
            if (!empty($target['property'])) {
                $property = current(array_keys($target['property']));
                $item = $target['property'][$property];
                $property_text = $item['title'];

                if ($item['price']) {
                    if ($target['is_promotion']) {
                        $price = $item['price_promote'];
                    } else {
                        $price = $item['price'];
                    }
                }
                if ($target['property'][$property]['detail']) {
                    $details = $target['property'][$property]['detail'];
                    foreach ($details as $property_detail => $item) {
                        $property_detail_text = $item['title'];

                        break;
                    }
                }
            }
        }

        $cart['id'] = $target['id'];
        $cart['title'] = $target['title'];
        $cart['amount'] = $amount;
        $cart['price'] = $price;
        $cart['property'] = $property;
        $cart['property_text'] = $property_text;
        $cart['property_detail'] = $property_detail;
        $cart['property_detail_text'] = $property_detail_text;

        $this->updateCart($cart);

        if (empty($product_lists[$id])) {
            $product_lists[$id] = $target;
            Session::write('product_lists', $product_lists);
        }
        $this->back();
    }
}
