# Vị trí hiển thị đầy đủ (placements) — sub-feature E — Design

> Chọn thiết bị / nền tảng / vị trí chi tiết (Feed, Stories, Reels, Marketplace, Video feeds…) cho từng nhóm quảng cáo.
> Ngày: 2026-06-05 · Trạng thái: approved (chủ uỷ quyền). Bám cây C. Làm trên `main`. ADR-0017.

## 1. Vấn đề hiện tại
StepPlacements ghi `node.placements` ('automatic'/'manual') + `placement_platforms` (4 giá trị thô) nhưng **KHÔNG tới Graph** (createAdSet chỉ gửi `targeting`). E làm chúng có hiệu lực + đầy đủ.

## 2. Cấu trúc placement (generic, trong node nhóm)
`node.placement_config = { automatic: bool, device_platforms: ['mobile'|'desktop'], publisher_platforms: ['facebook'|'instagram'|'messenger'|'audience_network'], positions: { facebook: [...], instagram: [...], messenger: [...], audience_network: [...] } }`. Generic (không tên field Graph) — connector dịch.

## 3. Connector (FB dịch sang targeting)
- `AdSetSpecDTO` thêm `?array $placementConfig = null`.
- `createAdSet`: nếu `placementConfig` có và `automatic` falsy → merge vào `targeting` (trước json_encode):
  - `device_platforms` (nếu không rỗng), `publisher_platforms` (nếu không rỗng),
  - `facebook_positions`/`instagram_positions`/`messenger_positions`/`audience_network_positions` từ `positions[<platform>]` (mỗi cái nếu không rỗng).
  - `automatic=true` ⇒ không thêm gì (Graph tự đặt).
- Giữ "core không biết sàn": cấu trúc generic ở DTO; tên field Graph chỉ ở connector.

## 4. Mapper
`adSet(...)`: đọc `node.placement_config` → truyền `placementConfig` vào AdSetSpecDTO.

## 5. Frontend (StepPlacements đầy đủ)
- Segmented Tự động / Thủ công (ghi `placement_config.automatic`).
- Thủ công:
  - **Thiết bị**: Checkbox [Điện thoại=mobile, Máy tính=desktop].
  - **Nền tảng**: Checkbox [Facebook, Instagram, Messenger, Audience Network] (chọn nền tảng nào mới hiện vị trí của nó).
  - **Vị trí** theo nền tảng đã chọn:
    - Facebook: Bảng tin (feed), Marketplace, Video feeds (video_feeds), Tin (story), Reels (facebook_reels), Cột phải (right_hand_column), Tìm kiếm (search).
    - Instagram: Bảng tin (stream), Tin (story), Reels (reels), Khám phá (explore).
    - Messenger: Trang chủ (messenger_home), Tin (story).
    - Audience Network: Cổ điển (classic), Video thưởng (rewarded_video).
  - Ghi vào `node.placement_config` qua `updateAdSet`.
- Icons @ant-design/icons. Không emoji.

## 6. Không đụng / Testing
- Additive: createAdSet thêm bước merge (automatic ⇒ y như cũ). `placement_platforms` cũ bỏ qua (thay bằng placement_config).
- Test BE: createAdSet manual merge đúng publisher_platforms + facebook_positions + device_platforms vào targeting; automatic ⇒ không thêm. Mapper truyền placementConfig.
- Test FE: typecheck/lint.

## 7. Build sequence
1. Connector (AdSetSpecDTO.placementConfig + createAdSet merge) + Mapper + test.
2. FE StepPlacements đầy đủ.

## 8. Giới hạn / nối tiếp
- Giới hạn HĐH/phiên bản (user_os/user_device) là follow-up nhỏ (thêm vào placement_config sau).
