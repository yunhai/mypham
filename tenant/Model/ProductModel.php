<?php

use Mp\Model\Product;

class ProductModel extends Product
{
    use \Mp\Lib\Traits\Extension;

    public function field()
    {
        return [
            'string_1' => 'promote',
            'string_2' => 'store_id',
            'string_3' => 'manufacturer',
            'string_4' => 'promote_start',
            'string_5' => 'promote_end',
            'text_1' => [
                'point',
                'weight',
                'capacity',
                'option',
                'option_price',
                'option_promotion',
                'code',
                'gallery',
                'files'
            ]
        ];
    }

    public function promotion(&$target = [])
    {
        $today = date('Y-m-d');

        $target['discount'] = 0;
        if ($target['price'] && $target['promote_start'] <= $today && $today <= $target['promote_end']) {
            $discount = ((int) (($target['promote'] / $target['price']) * 100));
            if ($discount) {
                $target['discount'] = 100 - $discount;
            }
        }
    }

    // public function beforeSave(&$data = [])
    // {
    //     if (isset($data['option'])) {
    //         foreach ($data['option'] as $index => $value) {
    //             if (!($value) || empty($data['option_price'][$index])) {
    //                 unset($data['option'][$index], $data['option_price'][$index]);
    //             }
    //         }
    //
    //         $data['option_promotion'] = array_intersect_key($data['option_promotion'], $data['option']);
    //     }
    // }
}
