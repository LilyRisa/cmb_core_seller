<?php

namespace Tests\Unit\Admin;

use CMBcoreSeller\Modules\Admin\Notifications\AdminAlertNotification;
use CMBcoreSeller\Modules\Admin\Notifications\NotificationTypeCatalog;
use Illuminate\Notifications\AnonymousNotifiable;
use Tests\TestCase;

/**
 * Bảo vệ mục tiêu "mở rộng loại thông báo mới không sửa dispatcher/CRUD/UI" — nếu ai
 * thêm 1 code vào NotificationTypeCatalog mà quên viết nhánh nội dung email tương ứng
 * trong AdminAlertNotification::toMail(), test này FAIL ngay ở CI thay vì im lặng
 * throw lúc job thật sự chạy (InvalidArgumentException, chết sau 3 lần retry).
 */
class AdminAlertNotificationCatalogCoverageTest extends TestCase
{
    public function test_every_catalog_type_has_a_mail_branch(): void
    {
        foreach (array_keys(NotificationTypeCatalog::all()) as $type) {
            $mail = (new AdminAlertNotification($type, []))->toMail(new AnonymousNotifiable);

            $this->assertNotEmpty($mail->subject, "Loại thông báo \"{$type}\" chưa có nhánh nội dung email trong AdminAlertNotification::toMail().");
        }
    }
}
