<?php
    return [
        'module' => [
                'post' => 'bai-viet',
                'makeup' => 'cam-nang-lam-dep',
                'advisory' => 'tu-van',
                'collection' => 'bo-suu-tap',
                'customer' => 'cham-soc-khach-hang',
                'product' => 'san-pham',
                'product-gallery' => 'album-san-pham',
                'product-files' => 'hinh-anh-san-pham',
                'store' => 'he-thong-chi-nhanh',
                'page' => 'trang-tinh',
                'banner' => 'banner',
                'contact' => 'lien-he',
                'manufacturer' => 'thuong-hieu'
        ],
        'seo' => [
            'prefix' => [
                'post' => [
                    'detail' => 'bai-viet/chi-tiet'
                ],
                'makeup' => [
                    'detail' => 'cam-nang-lam-dep'
                ],
                'advisory' => [
                    'detail' => 'tu-van'
                ],
                'collection' => [
                    'detail' => 'bo-suu-tap'
                ],
                'customer' => [
                    'detail' => 'cham-soc-khach-hang'
                ],
                'store' => [
                    'detail' => 'he-thong-chi-nhanh'
                ],
                'page' => [
                    'detail' => ''
                ],
                'banner' => [
                    'detail' => ''
                ],
                'manufacturer' => [
                    'detail' => 'thuong-hieu'
                ],
            ]
        ],
        'status' => [
            'default' => [
                1 => 'Hiển thị',
                0 => 'Ẩn'
            ],
            'order' => [
                3 => 'Từ chối',
                2 => 'Hoàn tất',
                1 => 'Đang xử lý',
                0 => 'Chờ duyệt'
            ],
            'product' => [
                3 => 'Bán chạy',
                // 2 => 'Sản phẩm hot',
                1 => 'Hiển thị',
                0 => 'Ẩn'
            ],
            'store' => [
                2 => 'Trang chủ',
                1 => 'Hiển thị',
                0 => 'Ẩn'
            ],
            'advisory' => [
                2 => 'Chờ duyệt',
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
        'form' => [
            'gender' => [
                1 => 'Nam',
                0 => 'Nữ'
            ]
        ]
    ];
