# Cách làm việc (Ways of Working)

**Status:** Stable · **Cập nhật:** 2026-05-11

## 1. Nguyên tắc chống lan man (đọc trước mỗi sprint)
1. **Chỉ làm việc thuộc phase hiện tại** (xem `00-overview/roadmap.md`). Ý tưởng ngoài phase ⇒ ghi vào backlog, **không làm xen**.
2. **Tính năng lớn ⇒ viết spec trước** (`docs/specs/`), được duyệt rồi mới code. PR phải link tới spec.
3. **Quyết định kiến trúc ⇒ viết ADR** (`docs/01-architecture/adr/`). Không "quyết định miệng".
4. **Đổi tài liệu trước, code sau.** Nếu code mâu thuẫn với tài liệu ⇒ dừng, thống nhất, cập nhật tài liệu, rồi code.
5. **Tôn trọng luật phụ thuộc module** (`01-architecture/modules.md`) và **luật mở rộng** (`01-architecture/extensibility-rules.md`). Vi phạm = từ chối PR.
6. **Nhỏ và thường xuyên hơn to và hiếm**: PR nhỏ, merge thường, tránh nhánh sống lâu.

## 2. Git
- Nhánh chính: `main` (luôn deploy được). Làm việc trên nhánh: `feat/<phase>-<slug>`, `fix/<slug>`, `chore/<slug>`, `docs/<slug>`, `refactor/<slug>`.
- Commit: dạng Conventional Commits — `feat(orders): ...`, `fix(channels): ...`, `docs: ...`, `refactor(inventory): ...`, `test: ...`, `chore: ...`. Câu mô tả ngắn, rõ "thay đổi gì".
- Không commit secret/`.env`/file build/`vendor`/`node_modules` (có `.gitignore`).
- `main` được bảo vệ: merge qua PR + CI xanh + ≥1 review.
- Release: tag `vX.Y.Z` cắt từ `main` (semver: breaking → major; tính năng → minor; sửa lỗi → patch).

## 3. Pull Request — checklist (Definition of Done)
Một PR chỉ "Done" khi:
- [ ] Thuộc phase hiện tại; nếu là tính năng lớn → link tới spec trong `docs/specs/`.
- [ ] CI xanh: Pint, PHPStan, migrate, Pest (đủ ngưỡng coverage), ESLint, tsc, build FE, contract tests.
- [ ] Có test cho logic mới (unit/feature; contract test nếu đụng connector). Bug fix kèm test tái hiện bug.
- [ ] Không vi phạm luật module/luật mở rộng (không `if ($provider===...)` ở core, không import ruột module khác).
- [ ] `tenant_id` & policy cho mọi dữ liệu/endpoint mới.
- [ ] Migration reversible (hoặc giải thích); không phá schema bảng module khác.
- [ ] Tài liệu được cập nhật: `endpoints.md` nếu thêm API; `queues-and-scheduler.md` nếu thêm job; doc channel nếu đụng connector; ADR nếu có quyết định kiến trúc; `roadmap.md` nếu hoàn thành mục lớn.
- [ ] Không log PII/secret; xử lý lỗi theo envelope chuẩn; i18n cho chuỗi hiển thị.
- [ ] Mô tả PR: làm gì, vì sao, ảnh hưởng, cách test thủ công (nếu cần).
- [ ] ≥1 reviewer approve. Reviewer kiểm: đúng phạm vi, đúng kiến trúc, có test, dễ bảo trì.

## 4. Review
- Review trong ~1 ngày làm việc. Ưu tiên: đúng hướng (phase/kiến trúc) > đúng nghiệp vụ > chất lượng code > nit. Nit ⇒ "nit:" và không chặn merge.
- Không tự merge PR của mình nếu chưa có review (trừ docs nhỏ, theo thoả thuận team).

## 5. Sprint / nhịp làm việc
- Nhịp 1–2 tuần. Đầu sprint: chốt mục tiêu **trong phase hiện tại**, ưu tiên những việc trên đường găng (vd hoàn thiện TikTok khi chờ API Shopee/Lazada).
- Demo cuối sprint theo **Exit criteria của phase**.
- Backlog 1 nơi (issue tracker); mỗi item ghi rõ phase, link spec nếu có.

## 6. Khi gặp việc "phá rào"
- Cần làm gì đó mâu thuẫn với tài liệu/kiến trúc ⇒ **dừng**, mở thảo luận (issue/ADR proposal), thống nhất, cập nhật tài liệu, rồi mới code. Không "code trước rồi tính sau" cho thay đổi kiến trúc.
- Hotfix khẩn cấp prod: cho phép nhánh `hotfix/*` merge nhanh, nhưng phải bổ sung test + cập nhật tài liệu ngay sau.

## 7. Onboarding người mới
Đọc theo thứ tự ở `docs/README.md` → dựng môi trường theo `07-infra/environments-and-docker.md` → đọc `coding-standards.md` & `testing-strategy.md` → chọn một issue nhỏ trong phase hiện tại để làm quen.
