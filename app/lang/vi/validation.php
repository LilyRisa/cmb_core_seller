<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bản dịch tiếng Việt cho thông báo validation (APP_LOCALE=vi)
|--------------------------------------------------------------------------
| App hướng tới người dùng Việt Nam (xem CLAUDE.md). Trước đây không có thư
| mục lang/ nào nên các rule như Password::min(8)->letters()->numbers()
| ->symbols() trả về message tiếng Anh. File này phủ các key hay gặp ở
| luồng đăng ký / cập nhật hồ sơ. Key thiếu sẽ fallback về tiếng Anh.
*/

return [
    'required' => 'Vui lòng nhập :attribute.',
    'email' => ':attribute không hợp lệ.',
    'unique' => ':attribute đã được sử dụng.',
    'confirmed' => 'Xác nhận :attribute không khớp.',
    'string' => ':attribute phải là chuỗi ký tự.',
    'boolean' => ':attribute phải là true hoặc false.',

    'max' => [
        'string' => ':attribute không được vượt quá :max ký tự.',
    ],

    'min' => [
        'string' => ':attribute phải có ít nhất :min ký tự.',
    ],

    // Rule Illuminate\Validation\Rules\Password
    'password' => [
        'letters' => 'Mật khẩu phải chứa ít nhất một chữ cái.',
        'mixed' => 'Mật khẩu phải chứa ít nhất một chữ hoa và một chữ thường.',
        'numbers' => 'Mật khẩu phải chứa ít nhất một chữ số.',
        'symbols' => 'Mật khẩu phải chứa ít nhất một ký tự đặc biệt.',
        'uncompromised' => 'Mật khẩu này đã xuất hiện trong các vụ rò rỉ dữ liệu. Vui lòng chọn mật khẩu khác.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tên thuộc tính tùy chỉnh
    |--------------------------------------------------------------------------
    */
    'attributes' => [
        'name' => 'tên',
        'email' => 'email',
        'password' => 'mật khẩu',
        'current_password' => 'mật khẩu hiện tại',
        'tenant_name' => 'tên gian hàng',
    ],
];
