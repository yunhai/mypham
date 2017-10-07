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

    public function add()
    {
        $request = App::mp('request');

        $id = $request->query[2];
        $extends = [
            'string_1' => 'promote',
            'string_3' => 'manufacturer',
            'string_4' => 'promote_start',
            'string_5' => 'promote_end',
            'text_1' => [
                'point',
                'option'
            ]
        ];

        $option = [
            'select' => 'id, title, price, file_id',
            'where' => 'id = '. $id
        ];

        $target = App::load('product', 'service')->get($option, $extends);
        $this->refer(['file' => [$target[$id]['file_id']]]);

        if (empty($target[$id])) {
            abort('NotFoundException');
        }

        $target = $target[$id];
        $target['model'] = 'product';

        if (isset($request->data)) {
            $target['amount'] = $request->data['amount'];
            $selected_option = isset($request->data['option']) ? $request->data['option'] : '';
            $target['selected-option'] = $target['option'][$selected_option];

            if (!empty($target['option_price'])) {
                $selected = $request->data['option'];
                $target['price'] = $target['option_price'][$selected];
                $target['promote'] = $target['option_promotion'][$selected] ?: 0;
            }
        } else {
            $target['amount'] = 1;
            $selected_option = isset($target['option']) ? current($target['option']) : '';
            $target['selected-option'] = $target['option'][$selected_option];
        }

        $today = $today = date('Y-m-d');
        if ($target['promote_start'] <= $today && $today <= $target['promote_end']) {
            $target['price'] = $target['promote'] ?: $target['price'];
        }

        $this->updateCart($target);
        $this->reload(App::mp('request')->referer(), 'add');
    }
}
