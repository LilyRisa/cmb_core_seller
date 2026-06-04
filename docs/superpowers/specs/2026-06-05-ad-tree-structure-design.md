# Ad tree structure — nhiều nhóm/nhiều quảng cáo (sub-feature C) — Design

> Mở rộng wizard tạo quảng cáo: 1 chiến dịch → NHIỀU nhóm → NHIỀU quảng cáo (như Ads Manager).
> Ngày: 2026-06-05 · Trạng thái: approved (chủ uỷ quyền tự quyết, ưu tiên dễ mở rộng). ADR-0017.

## 1. Mục tiêu
Cho phép 1 `AdDraft` mô tả một **cây**: campaign → adsets[] → ads[]. Publish tạo cả cây (resume-first idempotent). Thiết kế để **dễ mở rộng** cho các sub-feature sau (B ngân sách 2 cấp, E placements, F geo, H clone) bám vào cùng cây này.

Ràng buộc: tương thích ngược draft cũ (shape phẳng v1); additive; integration layer không biết tên sàn.

## 2. Payload — cây chuẩn hoá
```jsonc
payload = {
  // campaign-level
  // (objective + name vẫn ở cột AdDraft.objective/name như cũ)
  "adsets": [
    {
      "key": "<uuid client>",            // ổn định cho wizard/clone
      "name": "Nhóm 1",
      "budget": { "daily_major": 150000 },
      "targeting": { ...graph spec... },
      "placements": "automatic" | "manual",
      "placement_platforms": ["facebook_feed", ...],
      "schedule": { "start_time": null },
      "external_id": null,               // điền sau khi publish (resume)
      "ads": [
        { "key": "<uuid>", "name": "QC 1", "external_id": null,
          "creative": { "mode":"page_post"|"new", "page_id","page_post_id","image_hash","primary_text","headline","link_url","cta" } }
      ]
    }
  ]
}
```
- **Ngân sách ở cấp NHÓM** trong C (mỗi adset 1 budget). Cấp chiến dịch/CBO là sub-feature **B** (thêm `payload.campaign.budget_mode` sau).
- `external_id` lưu NGAY trong node (không dùng cột phẳng cho adset/ad nữa vì có nhiều). `AdDraft.campaign_external_id` (cột) giữ cho campaign.

## 3. Chuẩn hoá ngược (1 code path)
`AdDraftTree::normalize(array $payload): array{adsets: list<...>}` (Marketing support):
- Có `payload['adsets']` (mảng) → dùng nguyên.
- Không → **bọc shape phẳng v1** (`payload.targeting/placements/placement_platforms/budget/schedule/creative`) thành `adsets: [ { name:'Nhóm 1', budget, targeting, placements, placement_platforms, schedule, ads: [ { name:'Quảng cáo 1', creative } ] } ]`.
- Luôn trả về cấu trúc cây ⇒ mapper/publish chỉ xử lý 1 dạng. Draft cũ vẫn publish được.

## 4. Mapper (cây)
`AdDraftSpecMapper` (mở rộng, vẫn pure):
- `campaign(AdDraft): CampaignSpecDTO` (như cũ).
- `adSet(AdDraft, array $adsetNode, string $campaignExternalId, string $currency): AdSetSpecDTO` — đọc node.
- `ad(AdDraft, array $adNode, string $adSetExternalId): AdSpecDTO` — đọc node.
- Helper `adsetNodes(AdDraft): list<array>` = `AdDraftTree::normalize($draft->payload)['adsets']`.

## 5. Publish cây (resume-first)
`PublishAdDraft` job (mở rộng):
1. Campaign: nếu `campaign_external_id` rỗng → createCampaign → lưu cột.
2. Với mỗi adset node (theo index):
   - nếu node `external_id` rỗng → createAdSet(spec với campaignExternalId) → ghi `external_id` vào node → **save payload**.
   - với mỗi ad node: nếu rỗng → createAd(spec với adSetExternalId = node.external_id) → ghi `external_id` vào ad node → save payload.
3. status=published khi hết. Lỗi giữa chừng → status=failed, last_error; node đã tạo giữ external_id ⇒ re-publish resume từ node lỗi.
- Ghi external_id vào payload: thao tác trên mảng payload (copy), set theo index, `$draft->payload = $copy; $draft->save()` sau mỗi node (idempotent checkpoint).

## 6. Frontend (wizard nhiều nhóm/quảng cáo)
- Zustand store: thêm `adsets: AdSetNode[]` (mỗi node có `key`, fields, `ads: AdNode[]`); actions `addAdSet/removeAdSet/updateAdSet/addAd/removeAd/updateAd`. Giữ tương thích: khi load draft cũ phẳng → normalize sang 1 adset/1 ad ở FE.
- Bước "Nhóm quảng cáo": danh sách adset (Tabs/Collapse), nút "＋ Thêm nhóm", mỗi nhóm có targeting + placements + budget. Bước "Nội dung": trong adset đang chọn, danh sách ad, nút "＋ Thêm quảng cáo".
- Autosave gửi `payload.adsets`. Preview/publish dùng cây.
- Icons @ant-design/icons; clone (Ctrl+C/V) là sub-feature H (chỉ chừa `key` sẵn).

## 7. Không đụng luồng khác / Testing
- Additive: connector create methods (Plan 1) không đổi; chỉ mapper + publish + store + wizard mở rộng. Draft cũ chạy qua normalize.
- Test BE: `AdDraftTree::normalize` (cây giữ nguyên; phẳng→bọc 1 adset/1 ad). Mapper cây (adSet/ad theo node). `PublishAdDraft` tạo 2 adset × 2 ad (Http::fake) ghi external_id vào payload; resume bỏ qua node đã có id; lỗi adset thứ 2 → failed, adset 1 + ads của nó giữ id.
- Test FE: typecheck/lint; store add/remove adset/ad.

## 8. Build sequence
1. `AdDraftTree::normalize` (support) + test.
2. `AdDraftSpecMapper` cây (adsetNodes/adSet(node)/ad(node)) + test.
3. `PublishAdDraft` cây (resume-first, ghi external_id vào payload) + test.
4. FE store cây (adsets/ads actions) + test typecheck.
5. FE wizard: bước Nhóm (danh sách + thêm/xoá) + bước Nội dung (ads trong nhóm) + preview/publish.

## 9. Giới hạn / nối tiếp
- C chỉ adset-level budget; **B** thêm campaign budget/CBO trên cùng cây.
- **H** (clone Ctrl+C/V) dựa vào `key` + actions store của C.
- E/F/D/G mở rộng node adset (placements/geo/pixel) hoặc creative — không đổi cấu trúc cây.
