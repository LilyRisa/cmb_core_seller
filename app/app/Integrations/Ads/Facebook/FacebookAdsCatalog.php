<?php

namespace CMBcoreSeller\Integrations\Ads\Facebook;

/**
 * Nguồn sự thật giá trị quảng cáo Facebook hợp lệ theo Graph API (v25) — MỘT chỗ duy nhất
 * cho: vị trí (placement) hợp lệ/khai tử/desktop-only, danh sách objective, và JSON Schema
 * mô tả toàn bộ cây chiến dịch để AI tự điền.
 *
 * Tổ hợp objective × optimization_goal × billing_event × CTA giữ ở {@see FacebookObjectiveMap}
 * (đã có); catalog này tham chiếu lại để không trùng lặp.
 */
final class FacebookAdsCatalog
{
    /** Vị trí Meta đã KHAI TỬ ở Graph hiện tại — gửi lên là reject CẢ ad set (subcode 2490562). */
    public const DEPRECATED_FB_POSITIONS = ['video_feeds'];

    /** Vị trí CHỈ chạy desktop — gỡ khi quảng cáo không nhắm thiết bị desktop. */
    public const DESKTOP_ONLY_FB_POSITIONS = ['right_hand_column'];

    /** Vị trí CHỈ chạy mobile — gỡ khi quảng cáo không nhắm mobile (vd Stories mobile-only). */
    public const MOBILE_ONLY_FB_POSITIONS = ['story'];

    /** Vị trí FB bắt buộc kèm `feed` (FB reject nếu chọn mà thiếu feed). */
    public const FB_POSITIONS_REQUIRING_FEED = ['marketplace', 'search', 'profile_feed', 'notification'];

    /**
     * Vị trí hợp lệ mỗi platform (v25 — theo doc Placement Targeting) — đã loại vị trí khai tử.
     * Messenger: KHÔNG có `messenger_home` (Meta khai tử Messenger Inbox) — chỉ `story` (mobile-only)
     * và `sponsored_messages` (loại chiến dịch riêng, không phơi ở wizard).
     */
    public const POSITIONS = [
        'facebook' => ['feed', 'marketplace', 'story', 'facebook_reels', 'right_hand_column', 'search', 'instream_video'],
        'instagram' => ['stream', 'story', 'reels', 'explore', 'explore_home'],
        'messenger' => ['story'],
        'audience_network' => ['classic', 'rewarded_video'],
    ];

    public const DEVICE_PLATFORMS = ['mobile', 'desktop'];

    public const PUBLISHER_PLATFORMS = ['facebook', 'instagram', 'messenger', 'audience_network'];

    /**
     * Lọc vị trí trước khi gửi FB — một vị trí sai làm FB reject CẢ ad set. Thứ tự:
     *  1. Chỉ giữ giá trị NẰM TRONG tập hợp lệ v25 (gỡ khai tử + giá trị lạ như `messenger_home`).
     *  2. Gỡ vị trí desktop-only khi không nhắm desktop; mobile-only khi không nhắm mobile.
     *  3. FB: nếu chọn marketplace/search/... mà thiếu `feed` thì tự thêm `feed` (FB bắt buộc).
     *
     * @param  list<string>  $positions
     * @param  list<string>  $devices  rỗng = mọi thiết bị
     * @return list<string>
     */
    public static function sanitizePlacements(string $platform, array $positions, array $devices): array
    {
        $valid = self::POSITIONS[$platform] ?? null;
        if ($valid !== null) {
            $positions = array_filter($positions, fn ($p) => in_array($p, $valid, true));
        }

        $mobile = $devices === [] || in_array('mobile', $devices, true);
        $desktop = $devices === [] || in_array('desktop', $devices, true);

        if ($platform === 'facebook') {
            if (! $desktop) {
                $positions = array_filter($positions, fn ($p) => ! in_array($p, self::DESKTOP_ONLY_FB_POSITIONS, true));
            }
            if (! $mobile) {
                $positions = array_filter($positions, fn ($p) => ! in_array($p, self::MOBILE_ONLY_FB_POSITIONS, true));
            }
            $positions = array_values($positions);
            $needsFeed = array_intersect($positions, self::FB_POSITIONS_REQUIRING_FEED) !== [];
            if ($needsFeed && ! in_array('feed', $positions, true)) {
                $positions[] = 'feed';
            }

            return $positions;
        }

        // Messenger Stories mobile-only (giống FB Stories).
        if ($platform === 'messenger' && ! $mobile) {
            $positions = array_filter($positions, fn ($p) => $p !== 'story');
        }

        return array_values($positions);
    }

    /** @return list<string> */
    public static function objectives(): array
    {
        return FacebookObjectiveMap::supported();
    }

    /**
     * JSON Schema mô tả TOÀN BỘ cây chiến dịch để LLM điền (structured output).
     * Không liệt kê vị trí khai tử nên AI không bao giờ chọn chúng.
     *
     * @return array<string,mixed>
     */
    public static function jsonSchema(): array
    {
        // POSITIONS['facebook'] đã loại sẵn vị trí khai tử (nguồn khai báo sạch).
        $fbPositions = self::POSITIONS['facebook'];

        return [
            'type' => 'object',
            'required' => ['campaign', 'adsets'],
            'properties' => [
                'campaign' => [
                    'type' => 'object',
                    'required' => ['objective', 'budget_mode'],
                    'properties' => [
                        'objective' => ['type' => 'string', 'enum' => self::objectives()],
                        'budget_mode' => ['type' => 'string', 'enum' => ['campaign', 'adset'], 'description' => 'campaign=CBO (ngân sách ở chiến dịch), adset=ABO (ngân sách ở nhóm)'],
                        'daily_budget_major' => ['type' => 'integer', 'description' => 'Ngân sách/ngày đơn vị tiền tệ chính (VND nguyên), set khi budget_mode=campaign'],
                    ],
                ],
                'adsets' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'items' => [
                        'type' => 'object',
                        'required' => ['name', 'ads'],
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'budget' => [
                                'type' => 'object',
                                'properties' => ['daily_major' => ['type' => 'integer', 'description' => 'Ngân sách/ngày của nhóm (VND nguyên), set khi budget_mode=adset']],
                            ],
                            'targeting' => [
                                'type' => 'object',
                                'properties' => [
                                    'geo_locations' => ['type' => 'object', 'description' => 'vd {"countries":["VN"]}'],
                                    'age_min' => ['type' => 'integer', 'minimum' => 13, 'maximum' => 65],
                                    'age_max' => ['type' => 'integer', 'minimum' => 13, 'maximum' => 65],
                                    'genders' => ['type' => 'array', 'items' => ['type' => 'integer', 'enum' => [1, 2]], 'description' => '1=nam,2=nữ; bỏ trống=tất cả'],
                                ],
                            ],
                            'placement_config' => [
                                'type' => 'object',
                                'properties' => [
                                    'automatic' => ['type' => 'boolean', 'description' => 'true = Advantage+ (vị trí tự động) — khuyến nghị'],
                                    'device_platforms' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => self::DEVICE_PLATFORMS]],
                                    'publisher_platforms' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => self::PUBLISHER_PLATFORMS]],
                                    'positions' => [
                                        'type' => 'object',
                                        'properties' => ['facebook' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => $fbPositions]]],
                                    ],
                                ],
                            ],
                            'schedule' => [
                                'type' => 'object',
                                'properties' => [
                                    'start_time' => ['type' => 'string', 'description' => 'ISO-8601 giờ bắt đầu (theo timezone tài khoản)'],
                                    'end_time' => ['type' => 'string', 'description' => 'ISO-8601 (tuỳ chọn)'],
                                ],
                            ],
                            'conversion' => [
                                'type' => 'object',
                                'description' => 'Chỉ cho objective=conversions',
                                'properties' => [
                                    'pixel_id' => ['type' => 'string'],
                                    'custom_event_type' => ['type' => 'string', 'description' => 'vd COMPLETE_REGISTRATION, PURCHASE'],
                                ],
                            ],
                            'ads' => [
                                'type' => 'array',
                                'minItems' => 1,
                                'items' => [
                                    'type' => 'object',
                                    'required' => ['name', 'creative'],
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                        'creative' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'mode' => ['type' => 'string', 'enum' => ['page_post', 'new']],
                                                'page_id' => ['type' => 'string'],
                                                'page_post_id' => ['type' => 'string', 'description' => 'dùng khi mode=page_post'],
                                                'link_url' => ['type' => 'string'],
                                                'headline' => ['type' => 'string'],
                                                'primary_text' => ['type' => 'string'],
                                                'cta' => ['type' => 'string'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
