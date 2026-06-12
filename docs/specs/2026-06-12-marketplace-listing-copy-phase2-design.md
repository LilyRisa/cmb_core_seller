# Marketplace listing copy phase 2

- Date: 2026-06-12
- Status: Implemented slice
- Scope: copy an existing `ListingDraft` to another connected marketplace shop.

## Goal

Sellers can copy a prepared or published listing to another shop. The target may be on the same provider or a different provider. The backend remains the only place that holds marketplace credentials and calls marketplace APIs.

## Rules

- Source: `ListingDraft` created from a master `Product`.
- Target: active `ChannelAccount` owned by the same tenant.
- Same provider: copy category, brand, attributes, media, logistics, SKU prices/stock/package data, then revalidate. If the copied data still passes the target provider validator, the draft becomes `ready`.
- Different provider: copy only reusable content: description, images, SKU prices/stock/package data. Clear category, brand, provider-specific attributes, and logistics so the target draft stays behind the edit gate.
- Never copy `external_item_id`, marketplace SKU ids, QC status, `pushed_at`, or last error.
- One `(product, target shop)` draft remains the invariant. If a non-live draft already exists, clone overwrites it. If a live target listing already exists, the API returns `409`.
- Push still uses the existing `listings` queue and `ProductPushBatch` progress modal.

## API

- `POST /api/v1/listings/{id}/clone`
- Request: `{ "channel_account_id": 123 }`
- Response: `201 { data: ListingDraftResource }`

## UI

Product publishing is its own sidebar group **"Đăng bán sàn"**, split into three pages that all read from `GET /products` (which embeds `listings[]`) — no new list endpoints:

1. **Sản phẩm copy** (`/marketplace/products`) — master products (mostly copied via the Chrome extension). Action: "Tạo nháp sàn" → pick a shop → opens the editor drawer.
2. **Chờ đẩy lên sàn** (`/marketplace/to-push`) — listing drafts with status `ready` / `draft` / `failed`. Push (single + bulk), edit, and copy.
3. **Đã có trên sàn** (`/marketplace/on-channel`) — real `ChannelListing`s synced from the shops (sync + display + edit title/description/images/price back to the marketplace). See `2026-06-12-marketplace-listing-edit-design.md`.

Pages 2 and 3 share one component (`ListingDraftsTable`) that flattens `product.listings[]`, filters by status, and hosts the editor drawer, the copy modal, and the push-progress modal.

The copy modal (`CloneListingModal`) asks only for the **target shop** (the source listing is the row). It excludes the source shop. If the target provider differs from the source provider, it shows a cross-platform notice and the page opens the target draft editor after creation so the seller can complete category, attributes, brand, and logistics before pushing.

Legacy route `/listings` redirects to `/marketplace/products`.
