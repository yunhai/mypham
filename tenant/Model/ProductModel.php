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
            'string_6' => 'skin_mode',
            'string_7' => 'state_mode',
            'string_8' => 'star',
            'string_9' => 'comment',
            'text_1' => [
                'property',
                'display_mode',
                'default_mode',
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
        $target['discount'] = 0;
        $target['is_promotion'] = $this->isValidPromotion($target);

        $target['final_price'] = $target['price'];

        if ($target['is_promotion']) {
            $target['promote'] = empty($target['promote']) ? 0 : $target['promote'];
            $discount = ((int) (($target['promote'] / $target['price']) * 100));
            if ($discount) {
                $target['discount'] = 100 - $discount;
            }
            $target['final_price'] = $target['promote'];
        }

        if (isset($target['property'])) {
            foreach ($target['property'] as &$property) {
                $property['discount'] = 0;
                $property['final_price'] = empty($property['price']) ? 0 : $property['price'];
                if ($target['is_promotion'] && $property['price_promote']) {
                    $discount = ((int) (($property['price_promote'] / $property['price']) * 100));
                    if ($discount) {
                        $property['discount'] = 100 - $discount;
                    }
                    $property['final_price'] = $property['price_promote'];
                }
            }
        }
    }

    public function isValidPromotion($target)
    {
        $today = date('Y-m-d');
        return $target['promote_start'] <= $today && $today <= $target['promote_end'];
    }

    public function updateInventory($product_id, $amount, $property_id, $positive = true)
    {
        $alias = $this->alias();
        $fields = "{$alias}.id, {$alias}.inventory";

        $target = $this->findById($product_id, $fields);
        $target = array_shift($target);

        if ($positive) {
            if ($amount > $target['inventory'] || $amount > $target['property'][$property_id]['inventory']) {
                return false;
            }
            
            $target['inventory'] -= $amount;
            $target['property'][$property_id]['inventory'] -= $amount;
        } else {
            $target['inventory'] += $amount;
            $target['property'][$property_id]['inventory'] += $amount;
        }

        $this->save($target);
        return true;
    }
}
