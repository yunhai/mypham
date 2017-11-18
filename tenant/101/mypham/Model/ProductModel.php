<?php

use Mp\Model\Product;

class ProductModel extends Product
{
    use \Mp\Lib\Traits\Extension;

    public function __construct()
    {
        parent::__construct();
        $this->extension();
    }

    public function extension()
    {
        $this->loadExtension(new \Mp\Model\Extension());
        $this->virtualField($this->field());
    }

    public function field()
    {
        return [
            'string_1' => 'promote',
            'string_2' => 'store_id',
            'string_3' => 'manufacturer_id',
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
    }

    public function checkPromotion(&$data = [], $list = true)
    {
        if ($list) {
            foreach ($data as &$target) {
                $this->promotion($target);
            }
        } else {
            $this->promotion($data);
        }

        return $data;
    }

    public function promotion(&$target = [])
    {
        $today = date('Y-m-d');

        $target['discount'] = 0;
        $target['is_promotion'] = false;

        $target['final_price'] = $target['price'];

        if ($target['price'] && $target['promote_start'] <= $today && $today <= $target['promote_end']) {
            $discount = ((int) (($target['promote'] / $target['price']) * 100));
            if ($discount) {
                $target['discount'] = 100 - $discount;
            }
            $target['final_price'] = $target['promote'];
            $target['is_promotion'] = true;
        }

        if (isset($target['property'])) {
            foreach ($target['property'] as &$property) {
                $price = $target['final_price'];
                if ($property['price']) {
                    $price = $property['price'];
                    if ($target['is_promotion']) {
                        $price = $property['price_promote'];
                    }
                }
                $property['final_price'] = $price;
            }
        }
    }
}
