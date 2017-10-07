<?php
    return [
        'module' => [
                'post' => 'bai-viet',
                'product' => 'san-pham',
                'page' => 'trang-tinh',
                'contact' => 'lien-he',
        ],
        'seo' => [
            'prefix' => [
                'post' => [
                    'detail' => 'bai-viet/chi-tiet'
                ],
                'page' => [
                    'detail' => 'trang/'
                ],
                'product' => [
                    'detail' => 'san-pham/chi-tiet'
                ]
            ]
        ],
        'status' => [
            'default' => [
                1 => 'Hiển thị',
                0 => 'Ẩn'
            ],
            'product' => [
                3 => 'Bán chạy từ shop',
                2 => 'Sản phẩm hot',
                1 => 'Hiển thị',
                0 => 'Ẩn'
            ],
            'contact' => [
                3 => 'Từ chối',
                2 => 'Hoàn tất',
                1 => 'Phản hồi',
                0 => 'Chờ duyệt'
            ]
        ],
        'channel' => [
            1 => 'Thông thường',
            2 => 'Quản trị'
        ],
    ];
