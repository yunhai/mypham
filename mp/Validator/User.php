<?php

namespace Mp\Validator;

use Mp\App;
use Mp\Core\Validator\Validator;

class User extends Validator
{
    public function def()
    {
        return [
            'account' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('5005', 'Tài khoản không được trống'),
                ],
                'format' => [
                    'rule' => ['accountFormat'],
                    'message' => __('5009', 'Tài khoản phải là địa chỉ email hoặc là ký tự, dấu "-", "_", "."'),
                ],
                'available' => [
                    'rule' => ['available'],
                    'message' => __('5004', 'Tài khoản đã được đăng ký'),
                ]
            ],
            'email' => [
                'format' => [
                    'rule' => ['email'],
                    'message' => __('5009', 'Email không hợp lệ'),
                ],
                'uniqueEmail' => [
                    'rule' => ['uniqueEmail'],
                    'message' => __('5004', 'Tài khoản đã được đăng ký'),
                ]
            ],
            'password' => [
                'lengthBetween' => [
                    'rule' => ['lengthBetween', 8, 32],
                    'message' => __('5006', 'Mật khẩu phải từ 8 đến 32 ký tự'),
                ],
                'format' => [
                    'rule' => ['passwordFormat'],
                    'message' => __('5008', 'Mật khẩu không được chứa khoảng trắng'),
                ]
            ],
            'confirm-password' => [
                'confirm' => [
                    'rule' => ['confirmPassword'],
                    'message' => __('5007', 'Mật khẩu xác nhận không chính xác'),
                ]
            ]
        ];
    }

    public function signUp()
    {
        return [
            'account' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('5005', 'Tài khoản không được trống'),
                ],
                'format' => [
                    'rule' => ['accountFormat'],
                    'message' => __('5009', 'Tài khoản phải là địa chỉ email hoặc là ký tự, dấu "-", "_", "."'),
                ],
                'available' => [
                    'rule' => ['available'],
                    'message' => __('5004', 'Tài khoản đã được đăng ký'),
                ]
            ],
            'email' => [
                'format' => [
                    'rule' => ['email'],
                    'message' => __('5009', 'Email không hợp lệ'),
                ],
                'uniqueEmail' => [
                    'rule' => ['uniqueEmail'],
                    'message' => __('5004', 'Email đã được đăng ký'),
                ]
            ],
            'password' => [
                'lengthBetween' => [
                    'rule' => ['lengthBetween', 8, 32],
                    'message' => __('5006', 'Mật khẩu phải từ 8 đến 32 ký tự'),
                ],
                'format' => [
                    'rule' => ['passwordFormat'],
                    'message' => __('5008', 'Mật khẩu không được chứa khoảng trắng'),
                ]
            ],
            'confirm-password' => [
                'confirm' => [
                    'rule' => ['confirmPassword'],
                    'message' => __('5007', 'Mật khẩu xác nhận không chính xác'),
                ]
            ],
            'fullname' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('5005', 'Họ tên không được trống'),
                ],
            ]
        ];
    }

    public function resetPassword()
    {
        return [
            'account' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('5005', 'Tài khoản không được trống'),
                ],
                'format' => [
                    'rule' => ['accountFormat'],
                    'message' => __('5009', 'Tài khoản phải là địa chỉ email hoặc là ký tự, dấu "-", "_", "."'),
                ]
            ],
            'password' => [
                'lengthBetween' => [
                    'rule' => ['lengthBetween', 8, 32],
                    'message' => __('5006', 'Mật khẩu phải từ 8 đến 32 ký tự'),
                ],
                'format' => [
                    'rule' => ['passwordFormat'],
                    'message' => __('5008', 'Mật khẩu không được chứa khoảng trắng'),
                ]
            ],
            'confirm-password' => [
                'confirm' => [
                    'rule' => ['confirmPassword'],
                    'message' => __('5007', 'Mật khẩu xác nhận không chính xác'),
                ]
            ]
        ];
    }

    public function passwordFormat($checked = '')
    {
        if (preg_match("/^\S+$/", $checked)) {
            return true;
        }

        return false;
    }

    public function accountFormat($checked = '')
    {
        if (preg_match('/^[A-Za-z][A-Za-z0-9-_.]{5,31}$/', $checked) ||
            filter_var($checked, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        return false;
    }

    public function available($checked = '', $data = [])
    {
        $model = App::load('user', 'model');
        $alias = $model->alias();
        $appId = App::mp('login')->targetId();

        $id = empty($data['id']) ? 0 : $data['id'];

        $option = [
            'select' => '' . $alias . '.id',
            'where' => '(' . $alias . '.account = "' . $checked . '" OR ' . $alias . '.email = "' . $checked .'") AND ' . $alias . '.id <> ' . $id . ' AND ' . $alias . '.status > 0 AND group.app_id = ' . $appId . ' AND group.status > 0 AND group.deleted is null AND group.channel = ' . $data['channel'],
            'join' => [
                [
                    'table' => 'group',
                    'alias' => 'group',
                    'type' => 'INNER',
                    'condition' => $alias . '.group_id = group.id'
                ],
            ],
            'limit' => 1,
        ];

        return empty($model->find($option, 'first'));
    }

    public function confirmPassword($target, $data = [])
    {
        return ($data['password'] == $data['confirm-password']);
    }

    public function updatePassword()
    {
        return [
            'current-password' => [
                'isCurrentPassword' => [
                    'rule' => ['isCurrentPassword'],
                    'message' => __('5009', 'Mật khẩu hiện tại không chính xác'),
                ]
            ],
            'confirm-password' => [
                'confirm' => [
                    'rule' => ['confirmPassword'],
                    'message' => __('5007', 'Mật khẩu xác nhận không chính xác'),
                ]
            ],
            'password' => [
                'lengthBetween' => [
                    'rule' => ['lengthBetween', 8, 32],
                    'message' => __('5006', 'Mật khẩu phải từ 8 đến 32 ký tự'),
                ],
                'format' => [
                    'rule' => ['passwordFormat'],
                    'message' => __('5008', 'Mật khẩu không được chứa khoảng trắng'),
                ],
                'passwordChanged' => [
                    'rule' => ['passwordChanged'],
                    'message' => __('5009', 'Mật khẩu mới giống với mật khẩu hiện tại'),
                ],
            ]
        ];
    }

    public function isCurrentPassword($password, $data = [])
    {
        $model = App::load('user', 'model');
        $alias = $model->alias();
        $id = $data['id'];
        $field = 'id, password';
        $info = $model->findById($id, $field);

        if (empty($info)) {
            return false;
        }

        return password_verify($password, $info[$alias]['password']);
    }

    public function passwordChanged($password, $data = [])
    {
        return $password != $data['current-password'];
    }

    public function uniqueEmail($checked = '', $data = [])
    {
        $model = App::load('user', 'model');
        $alias = $model->alias();

        $appId = App::mp('login')->targetId();

        $id = empty($data['id']) ? 0 : $data['id'];
        $option = [
            'select' => $alias . '.id',
            'where' => $alias . '.provider="local" AND ' . '(' . $alias . '.account = "' . $checked . '" OR ' . $alias . '.email = "' . $checked .'") AND ' . $alias . '.id <> ' . $id . ' AND group.app_id = ' . $appId . ' AND group.status > 0 AND group.deleted is null AND group.channel = ' . $data['channel'],
            'join' => [
                [
                    'table' => 'group',
                    'alias' => 'group',
                    'type' => 'INNER',
                    'condition' => $alias . '.group_id = group.id'
                ],
            ],
            'limit' => 1,
        ];

        return empty($model->find($option, 'first'));
    }
}
