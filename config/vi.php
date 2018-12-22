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
                'manufacturer' => 'thuong-hieu',
                'promotion' => 'khuyen-mai'
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
                'promotion' => [
                    'detail' => 'khuyen-mai'
                ]
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
                // 3 => 'Hot',
                2 => 'Bán chạy',
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
            ],
            'user' => [
                1 => 'Đang hoạt động',
                0 => 'Ngưng hoạt động'
            ],
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
        ],
        'product' => [
            'display_mode' => [
                '1' => 'Hình ảnh',
                '2' => 'Textbox'
            ],
            'skin_mode' => [
                '10' => 'Mọi loại da',
                '20' => 'Da dầu',
                '30' => 'Da khô',
                '40' => 'Da hỗn hợp',
                '50' => 'Da nhạy cảm',
                '60' => 'Da lão hóa',
                '70' => 'Da mụn',
            ],
            'state_mode' => [
                '10' => 'Dạng lỏng',
                '20' => 'Dạng kem',
                '30' => 'Dạng cây',
                '40' => 'Dạng gel',
                '50' => 'Dạng lotion',
                '60' => 'Dạng Cushion',
            ],
            'price_range' => [
                'nho-hon-100000' => 'Nhỏ hơn 100.000',
                '100000-200000' => '100.000 - 200.000',
                '200000-500000' => '200.000 - 500.000',
                'lon-hon-500000' => 'Lớn hơn 500.000',
            ],
            'filter' => [
                'price' => [
                    'nho-hon-100000' => '100000',
                    '100000-200000' => '100000-200000',
                    '200000-500000' => '200000-500000',
                    'lon-hon-500000' => '500000',
                ],
                'skin' => [
                    'moi-loai-da' => 10,
                    'da-dau' => 20,
                    'da-kho' => 30,
                    'da-hon-hop' => 40,
                    'da-nhay-cam' => 50,
                    'da-lao-hoa' => 60,
                    'da-mun' => 70,
                ],
                'state' => [
                    'dang-long' => 10,
                    'dang-kem' => 20,
                    'dang-cay' => 30,
                    'dang-gel' => 40,
                    'dang-lotion' => 50,
                    'dang-cushion' => 60,
                ],
            ],
            'filter_mode' => [
                'skin' => [
                    'moi-loai-da' => 'Mọi loại da',
                    'da-dau' => 'Da dầu',
                    'da-kho' => 'Da khô',
                    'da-hon-hop' => 'Da hỗn hợp',
                    'da-nhay-cam' => 'Da nhạy cảm',
                    'da-lao-hoa' => 'Da lão hóa',
                    'da-mun' => 'Da mụn',
                ],
                'state' => [
                    'dang-long' => 'Dạng lỏng',
                    'dang-kem' => 'Dạng kem',
                    'dang-cay' => 'Dạng cây',
                    'dang-gel' => 'Dạng gel',
                    'dang-lotion' => 'Dạng lotion',
                    'dang-cushion' => 'Dạng Cushion',
                ],
                'price' => [
                    'nho-hon-100000' => 'Nhỏ hơn 100.000',
                    '100000-200000' => '100.000 - 200.000',
                    '200000-500000' => '200.000 - 500.000',
                    'lon-hon-500000' => 'Lớn hơn 500.000',
                ],
            ],
            'order_mode' => [
                'thong-thuong' => 'Thông thường',
                'gia-tang-dan' => 'Giá tăng dần',
                'gia-giam-dan' => 'Giá giảm dần',
            ]
        ]
    ];
