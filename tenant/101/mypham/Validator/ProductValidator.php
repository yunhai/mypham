<?php

use Mp\Validator\Product;

class ProductValidator extends Product
{
    public function def()
    {
        return [
            'title' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('2011', 'Tên sản phẩm không được trống')
                ]
            ],
            'code' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('2011', 'Mã sản phẩm không được trống')
                ],
                'uniqueCode' => [
                    'rule' => ['uniqueCode'],
                    'message' => __('2011', 'Mã sản phẩm đã được sử dụng')
                ],
            ],
            'price' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('2011', 'Giá không được trống')
                ],
                'price' => [
                    'rule' => ['price'],
                    'message' => __('2011', 'Giá không hợp lệ')
                ]
            ],
            'manufacturer_id' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('2011', 'Thương hiệu không được trống')
                ]
            ],
            'promote_start' => [
                'date' => [
                    'rule' => ['date', 'Ymd'],
                    'message' => __('2011', 'Ngày bắt đầu không hợp lệ'),
                ],
                'promote' => [
                    'rule' => ['promote'],
                    'message' => __('2011', 'Thời gian khuyến mãi không hợp lệ')
                ]
            ],
            'promote_end' => [
                'date' => [
                    'rule' => ['date'],
                    'message' => __('2011', 'Ngày kết thúc không hợp lệ'),
                ]
            ],
        ];
    }

    public function price($checked = '', $data = [])
    {
        $checked = (int) $checked;

        return $checked > 0;
    }

    public function promote($checked = '', $data = [])
    {
        $begin = $checked;
        $end = empty($data['promote_end']) ? '' : $data['promote_end'];

        return $begin <= $end;
    }

    public function date($checked)
    {
        if (empty($checked)) {
            return true;
        }

        $tmp = (int) (preg_replace('/[^0-9.]+/', '', $checked));

        if ($tmp >= '19000101') {
            return preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $checked);
        }

        return false;
    }

    public function uniqueCode($checked, $data = [])
    {
        return true;
    }
}
