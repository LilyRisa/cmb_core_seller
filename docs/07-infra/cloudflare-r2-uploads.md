# Upload ảnh — Cloudflare R2 (object storage)

**Status:** Living document · **Cập nhật:** 2026-05-16

> Cấu hình & vận hành kho lưu trữ ảnh do người dùng tải lên (ảnh SKU, sau này ảnh sản phẩm…) trên **Cloudflare R2** (S3-compatible). Liên quan: [SPEC 0005 §7](../specs/0005-sku-pim-and-create-form.md), [`environments-and-docker.md`](environments-and-docker.md), [`portainer-deploy.md`](portainer-deploy.md). Cấu hình code: `config/filesystems.php` (disk `r2`), `config/media.php` (`media.disk`, giới hạn ảnh), `app/Support/MediaUploader.php`.

## 1. Cách hệ thống dùng R2
- Disk `r2` trong `config/filesystems.php` là **driver `s3`** trỏ tới endpoint R2. `config/media.php` → `media.disk` = `r2` khi `APP_ENV=production` (ngoài prod mặc định `public` ⇒ chạy local/test **không cần** credential cloud).
- `POST /api/v1/skus/{id}/image` (multipart `image`, quyền `products.manage`): validate ảnh (PNG/JPG/WEBP, ≤ `MEDIA_IMAGE_MAX_KB` KB ~5MB) → `MediaUploader::storeImage()` đặt object key `tenants/<tenantId>/skus/<ULID>.<ext>` lên disk `media.disk` → lưu `skus.image_url` (URL công khai) + `skus.image_path` (object key, để xoá/thay) → xoá ảnh cũ. `DELETE /api/v1/skus/{id}/image` xoá object + clear 2 cột. FE: trang "Thêm SKU đơn độc" giữ file ở client rồi gọi endpoint này sau khi tạo SKU; danh sách SKU hiển thị `image_url`.
- Ảnh được phục vụ **trực tiếp từ R2** qua URL công khai (`R2_URL`), không đi qua app/nginx.

## 2. Tạo bucket & token trên Cloudflare (làm một lần)
1. **Bật R2**: Cloudflare dashboard → **R2** → (nếu lần đầu) thêm payment method để kích hoạt (có hạn mức free hằng tháng; **không** tính phí egress).
2. **Tạo bucket**: R2 → *Create bucket* → đặt tên (vd `cmb-media-prod`), chọn location gần (vd APAC). Ghi lại tên bucket.
3. **Lấy Account ID**: ở trang R2 (hoặc bucket → Settings → *S3 API*) có dòng endpoint `https://<ACCOUNT_ID>.r2.cloudflarestorage.com`. Đó là `R2_ENDPOINT`.
4. **Tạo API token**: R2 → **Manage R2 API Tokens** → *Create API token* → Permissions = **Object Read & Write**, Scope = **chỉ bucket vừa tạo** (đừng để "all buckets"), TTL tuỳ. Nhận về **Access Key ID** + **Secret Access Key** (secret chỉ hiện một lần — lưu vào trình quản lý bí mật). Đó là `R2_ACCESS_KEY_ID` / `R2_SECRET_ACCESS_KEY`.
5. **Bật truy cập công khai cho ảnh** — chọn 1 trong 2:
   - **(Khuyên dùng) Custom domain**: bucket → *Settings* → **Public access → Custom Domains → Connect Domain** → nhập subdomain bạn quản lý trên Cloudflare DNS (vd `cdn.tenmiencuaban.com`). Cloudflare tự tạo bản ghi & cấp TLS. ⇒ `R2_URL=https://cdn.tenmiencuaban.com`. (Có CDN cache, ổn định, không lộ account id.)
   - **(Nhanh, để thử) r2.dev**: bucket → *Settings* → **Public access → R2.dev subdomain → Allow Access** ⇒ được URL dạng `https://pub-xxxxxxxx.r2.dev`. ⇒ `R2_URL=https://pub-xxxxxxxx.r2.dev`. *Lưu ý:* r2.dev có rate-limit, **không** dùng cho tải nặng/production thật.
   > Không cần bật public cho cả bucket nếu sau này muốn ảnh riêng tư (dùng signed URL) — nhưng ảnh sản phẩm thường để public.
6. **CORS**: vì app **upload qua backend** (server-to-R2), **không cần** cấu hình CORS. Chỉ khi nào chuyển sang **presigned PUT trực tiếp từ trình duyệt** mới phải thêm CORS policy cho bucket (cho `PUT` từ origin của SPA).

## 3. Biến môi trường (prod)
Đặt trong Portainer → Stack → **Environment variables** (xem `.env.example` để biết tên đầy đủ):

| Biến | Bắt buộc | Giá trị |
|---|---|---|
| `MEDIA_DISK` | ✅ | `r2` |
| `MEDIA_IMAGE_MAX_KB` | — | `5120` (mặc định) — giới hạn dung lượng mỗi ảnh |
| `R2_ACCESS_KEY_ID` | ✅ | Access Key ID của token R2 (bước 2.4) |
| `R2_SECRET_ACCESS_KEY` | ✅ | Secret Access Key của token R2 |
| `R2_BUCKET` | ✅ | tên bucket (bước 2.2) |
| `R2_ENDPOINT` | ✅ | `https://<ACCOUNT_ID>.r2.cloudflarestorage.com` (bước 2.3) |
| `R2_URL` | ✅ | URL công khai của bucket (bước 2.5: custom domain hoặc `pub-….r2.dev`) — **không** có dấu `/` ở cuối |
| `R2_DEFAULT_REGION` | — | `auto` (R2 không có region — để mặc định, đừng đặt `ap-southeast-1`…) |
| `R2_USE_PATH_STYLE_ENDPOINT` | — | `true` (mặc định; R2 chỉ hỗ trợ path-style) |

## 4. Thao tác đặc biệt khi deploy (⚠ đọc kỹ)
Stack prod chạy `RUN_MIGRATIONS=false` và **cache config mỗi lần container khởi động** (xem `portainer-deploy.md` §0, §3). Vì vậy, để bật R2:

1. **Cài lại dependency**: bản này thêm package `league/flysystem-aws-s3-v3`. Image prod build từ `Dockerfile` ⇒ khi **Update the stack** trên Portainer (build lại image) nó tự `composer install` lấy package mới. **Nếu** bạn deploy bằng cách khác mà không build lại image ⇒ phải `composer install --no-dev` rồi mới chạy.
2. **Đặt các biến `R2_*` + `MEDIA_DISK=r2`** trong Environment variables của stack **trước** khi recreate. Đổi env mà không redeploy ⇒ container vẫn dùng config cache cũ — phải **Update/Redeploy the stack** (hoặc tối thiểu recreate `cmb-app`, `cmb-worker`).
3. **Chạy migration** trong `cmb-app` (vì `RUN_MIGRATIONS=false`):
   ```sh
   php artisan migrate --force
   ```
   Bản này có 2 migration: `..._extend_skus_for_pim` (đã có ở đợt trước — kiểm `php artisan migrate:status`) và `..._add_image_path_to_skus`. Quên bước này ⇒ lỗi `column skus.image_path not found` khi upload.
4. **Kiểm nhanh**:
   ```sh
   php artisan tinker --execute="dump(config('media.disk')); Storage::disk('r2')->put('healthcheck.txt', now()); dump(Storage::disk('r2')->url('healthcheck.txt')); Storage::disk('r2')->delete('healthcheck.txt');"
   ```
   - `config('media.disk')` phải in `"r2"` (nếu in `"public"` ⇒ env chưa nạp / chưa redeploy).
   - URL in ra phải mở được trên trình duyệt (nếu 401/404 ⇒ chưa bật public access ở bước 2.5, hoặc `R2_URL` sai).
   - Lỗi `The provided token... / SignatureDoesNotMatch` ⇒ sai `R2_ACCESS_KEY_ID/SECRET` hoặc token không có quyền ghi / sai bucket scope. Lỗi `endpoint... region` ⇒ `R2_ENDPOINT` sai hoặc đặt `R2_DEFAULT_REGION` khác `auto`.
5. **Reverse proxy / CSP**: ảnh phục vụ từ `R2_URL` (domain Cloudflare hoặc custom domain), không qua nginx của app — không cần chỉnh `cmb-web`. Nếu có `Content-Security-Policy` chặn `img-src` ⇒ thêm domain `R2_URL` vào `img-src`.

## 5. Vận hành & lưu ý
- **Local/dev/test**: để `MEDIA_DISK` trống (mặc định `public`) — ảnh vào `storage/app/public`, cần `php artisan storage:link` để xem qua `/storage/...`. Test dùng `Storage::fake('public')`.
- **Xoá SKU**: hiện `DELETE /skus/{id}` (soft delete) **không** xoá object ảnh trên R2 (tránh mất dữ liệu khi khôi phục). Khi có job dọn rác / xoá cứng sẽ gọi `MediaUploader::delete($sku->image_path)`.
- **Đổi ảnh**: upload đè tạo object mới (ULID khác) rồi xoá object cũ ⇒ URL thay đổi (cache CDN của ảnh cũ tự hết hạn).
- **Backup**: object trong R2 **không** nằm trong backup DB. Cân nhắc bật *Object lifecycle* / versioning ở bucket, hoặc sao lưu định kỳ sang bucket khác nếu ảnh là dữ liệu quan trọng. (Bản ghi `image_url/image_path` thì có trong backup DB như mọi cột khác.)
- **Bảo mật token**: token R2 chỉ cần *Object Read & Write* trên đúng 1 bucket. Không dùng token global. Khi nghi lộ ⇒ Cloudflare → revoke token, tạo token mới, cập nhật env, redeploy.

## 6. Mở rộng sau này
- Tạo thumbnail nhiều kích thước khi upload (queue job dùng `intervention/image`), hoặc dùng **Cloudflare Images / Image Resizing** trên domain trước R2.
- Cho phép nhiều ảnh / gallery ⇒ bảng `sku_images` thay vì 1 cột; tái dùng cùng disk `media.disk`.
- Presigned PUT trực tiếp từ trình duyệt (giảm tải backend) ⇒ thêm CORS policy cho bucket + endpoint cấp presigned URL.
- Dùng chung disk `r2` cho ảnh sản phẩm, ảnh đại diện tenant, tệp xuất CSV/PDF…
