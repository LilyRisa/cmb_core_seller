<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Modules\Messaging\Services\WebPushSender;
use PHPUnit\Framework\TestCase;

/**
 * VAPID `sub` (RFC 8292 §2.1) phải là URI `mailto:` hoặc `https:`. Admin hay nhập sai
 * kiểu `mailto: <admin@x.com>` (dấu cách + ngoặc nhọn) ⇒ push service trả 403 ⇒ web push
 * "im lặng" không tới. normalizeSubject chuẩn hoá để không vỡ vì lỗi nhập liệu.
 */
class WebPushSubjectTest extends TestCase
{
    public function test_strips_space_and_angle_brackets_from_mailto(): void
    {
        $this->assertSame('mailto:admin@cmbcore.com', WebPushSender::normalizeSubject('mailto: <admin@cmbcore.com>'));
    }

    public function test_bare_email_gets_mailto_scheme(): void
    {
        $this->assertSame('mailto:admin@cmbcore.com', WebPushSender::normalizeSubject('admin@cmbcore.com'));
    }

    public function test_already_valid_mailto_unchanged(): void
    {
        $this->assertSame('mailto:admin@cmbcore.com', WebPushSender::normalizeSubject('mailto:admin@cmbcore.com'));
    }

    public function test_https_subject_preserved(): void
    {
        $this->assertSame('https://app.cmbcore.com', WebPushSender::normalizeSubject('  <https://app.cmbcore.com> '));
    }

    public function test_blank_stays_blank(): void
    {
        $this->assertSame('', WebPushSender::normalizeSubject('   '));
        $this->assertSame('', WebPushSender::normalizeSubject(''));
    }
}
