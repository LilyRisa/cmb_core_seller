<?php

namespace Tests\Unit\Channels;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use Normalizer;
use Tests\TestCase;

/**
 * Gỡ kết nối gian hàng yêu cầu gõ đúng tên. Tên shop của sàn (Shopee/TikTok/Lazada) có thể lưu ở dạng
 * Unicode NFD (tổ hợp dấu), trong khi chuỗi người dùng paste/gõ là NFC ⇒ so sánh theo byte trượt dù
 * nhìn giống hệt. matchesNameConfirmation() phải chuẩn hoá NFC + trim + gom khoảng trắng + lowercase.
 */
class ChannelAccountConfirmationTest extends TestCase
{
    public function test_matches_across_nfc_and_nfd_forms(): void
    {
        $nfd = Normalizer::normalize('Công Minh Store - âm thanh không giới hạn', Normalizer::FORM_D);
        $nfc = Normalizer::normalize('Công Minh Store - âm thanh không giới hạn', Normalizer::FORM_C);
        $this->assertNotSame($nfd, $nfc, 'sanity: NFD và NFC phải khác byte');

        $account = new ChannelAccount(['shop_name' => $nfd]);

        $this->assertTrue($account->matchesNameConfirmation($nfc), 'paste NFC phải khớp tên lưu NFD');
        $this->assertTrue($account->matchesNameConfirmation('  '.$nfc.'  '), 'khoảng trắng đầu/cuối bỏ qua');
        $this->assertTrue($account->matchesNameConfirmation(mb_strtoupper($nfc)), 'không phân biệt hoa thường');
        $this->assertFalse($account->matchesNameConfirmation('Tên gian hàng khác'), 'tên sai phải trượt');
    }

    public function test_collapses_repeated_whitespace(): void
    {
        $account = new ChannelAccount(['shop_name' => 'CMB audio  - lộc phát']); // 2 dấu cách
        $this->assertTrue($account->matchesNameConfirmation('CMB audio - lộc phát')); // 1 dấu cách
    }

    public function test_uses_display_name_alias_when_set(): void
    {
        $account = new ChannelAccount(['shop_name' => 'shop_goc', 'display_name' => 'Biệt danh']);
        $this->assertTrue($account->matchesNameConfirmation('biệt danh'));
        $this->assertFalse($account->matchesNameConfirmation('shop_goc'));
    }
}
