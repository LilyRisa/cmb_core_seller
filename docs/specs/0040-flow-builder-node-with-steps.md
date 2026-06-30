# SPEC 0040: Flow Builder kiểu "node-chứa-step" (ChatbotX-style) cho cả Facebook & Zalo

- **Trạng thái:** Draft (chốt hướng với người dùng 2026-06-30 — chọn kiến trúc node-with-steps).
- **Phase:** 7.x (Messaging) — đại tu Flow Builder (kế thừa flow-builder S1–S3).
- **Module:** Messaging (Services/Flows + FE features/messaging/flow). Reuse FlowEngine/FlowRun/FlowMatcher; KHÔNG đụng core/connector ngoài DTO.
- **Tác giả / Ngày:** Claude · 2026-06-30
- **Liên quan:** flow-builder S1–S3 (docs/superpowers/plans/2026-05-29-*), SPEC 0024 (Messaging), SPEC 0035 (per-page scoping), SPEC 0039 (Zalo OA). Tham khảo: ChatbotXIO/ChatbotX (`apps/builder/src/features/flows/react-flow`, `packages/flow-config`).
- **Memory:** [[zalo-oa-messaging-integration]] (flow reuse provider), [[per-page-messaging-scoping-spec0035]], [[ui-use-font-icons-not-emoji]], [[ui-avoid-select-prefer-radio]], [[test-verify-baseline]].

## 1. Vấn đề & mục tiêu

Flow builder hiện tại **"phẳng"**: mỗi node = 1 hành động, 8 loại node (`trigger, send_message, send_buttons, send_comment_reply, wait_reply, condition, ai_reply, end`), 1 form/node, không có "step" bên trong. ChatbotX (chuẩn tham khảo) dùng mô hình **node chứa danh sách step kéo-thả**: ít node (~8) nhưng mỗi node giữ một mảng `steps[]` chọn từ menu "+ Create" phân nhóm — nhờ đó có ~90 hành động (gửi text/ảnh/card, hỏi-đáp, gắn tag, set field, delay, điều kiện, gọi API, AI…) mà không phình số node.

**Mục tiêu:** đại tu builder sang mô hình **node-with-steps** đúng UX ChatbotX, **dùng chung cho Facebook lẫn Zalo** (flow đã mang `provider`, FlowMatcher khớp theo provider). Giữ giao diện đồng nhất (Ant Design), không mất flow cũ.

## 2. Quyết định thiết kế (chốt 2026-06-30)

1. **Kiến trúc = node-chứa-step** (người dùng chọn) — không phải "thêm node phẳng".
2. **Áp cho cả Facebook + Zalo** (cùng builder; step nào cần capability/connector thì gate theo capability, không theo tên sàn).
3. **Không mất flow cũ** — migrate mỗi node phẳng cũ → 1 node mới chứa 1 step tương đương.
4. **2 hạ tầng dùng chung** phải làm: (a) **kho biến/custom-field + tag của khách** (cho hỏi-đáp, set field, tag, điều kiện theo field); (b) **bộ hẹn-giờ resume** (cho delay theo thời gian).
5. Mọi step gate qua **capability/interface**, không hardcode provider.

## 3. Kiến trúc đích (theo ChatbotX, ánh xạ sang stack ta)

**Mô hình dữ liệu (FE + graph jsonb):**
- Node = `{ id, type, position, data: { name, isStartNode?, steps: Step[], quickReplies?: Button[] } }`. `type` ∈ tập node-container ít ỏi: `start` (trigger), `send_message` (chứa step gửi + action), `perform_action` (chỉ action), `split_traffic`, `delay`, `end`.
- Step = `{ id, stepType, ...config }`. `stepType` từ một **registry** (ánh xạ ChatbotX `allSteps`): mỗi step có `{ defaultFn, validator, editor (FE), viewer (FE), executor (BE) }`.
- Edge = `{ id, source, target, sourceHandle }`. Handle nguồn: "Continue" mặc định + 1 handle/quick-reply-button + handle màu `success/skip/failure` từ step (hỏi-đáp, API, AI), + handle/nhánh cho split_traffic.

**FE (React + AntD + @xyflow/react):**
- **Step registry** `allSteps` (FE) — bản AntD của ChatbotX `steps/index.tsx`: `Record<stepType, StepDefinition{ editor, viewer, defaultFn, label, icon, group }>`.
- **Node viewer**: card (header icon+tên) + render `steps[].viewer` (mini-card mỗi step) + quick-reply buttons (mỗi cái 1 handle) + hàng "Tiếp tục" với source handle (trừ split_traffic).
- **Node editor (Drawer/Sheet)**: danh sách step **sortable** (kéo sắp xếp, xoá, copy) + nút **"+ Tạo bước"** = dropdown phân nhóm (theo `nodes/*/menu.tsx`); chọn step → `defaultFn` → append.
- **Canvas**: giữ React Flow hiện có; thêm nút "+" thêm node (floating) hoặc giữ palette trái; edge có nút xoá khi hover; kéo handle nút ra vùng trống → auto tạo node mới + nối.

**BE (Services/Flows):**
- **Step-executor layer**: thêm `StepExecutor` interface (`stepType()`, `execute(StepContext): StepResult{ advance|wait|retry|branch(handle)|fail }`) + `StepExecutorRegistry`. Node executor (`SendMessageNodeExecutor`, `PerformActionNodeExecutor`) duyệt `steps[]` lần lượt; step trả `wait/retry` thì dừng (resume sau), `branch` thì theo handle. Mirror ChatbotX `runStepsAndQuickReplies`.
- Reuse `FlowEngine`/`FlowRun.context` (lưu con trỏ step + biến thu thập). `FlowGraphValidator` mở rộng: validate step hợp lệ + handle/exit theo step.
- **Capability gating** giữ nguyên cơ chế (interactive/template…); step gửi card/nút gate qua `InteractiveMessagingConnector`.

## 4. Phân phase (mỗi phase 1 plan + execute riêng)

**Phase 2A — Nền tảng node-with-steps (KHÔNG thêm tính năng mới, chỉ đổi mô hình):**
- Data model `steps[]`; step registry FE + BE (StepExecutor/Registry); node viewer render steps; node editor sortable + "+ Tạo bước"; engine chạy step trong node.
- Re-express tập hành động HIỆN CÓ thành step: `send_text`, `send_image/video/file` (gộp từ attachments), `send_buttons`(quick-reply), `condition`(keyword), `ai_reply`, `wait_reply`, `send_comment_reply`. Migrate flow cũ (flat node → node 1-step) qua migration + `present()` tương thích.
- **Kết quả:** builder trông & hoạt động như ChatbotX với năng lực hiện tại; nền cho 2B/2C. Validator + tests xanh.

> **TRẠNG THÁI 2A (2026-06-30 — ĐÃ LÀM, additive/non-breaking):** Plan `docs/superpowers/plans/2026-06-30-flow-node-with-steps-2a.md`. Đã ship cho **node `send_message`**: `node.data.steps[]` tùy chọn với 3 step `send_text`/`send_media`/`send_buttons`; `StepExecutor`+`StepExecutorRegistry` (mirror NodeExecutor) + 3 executor dùng lại `OutboundMessageService`; `SendMessageNodeExecutor` duyệt steps idempotent (cursor `run.context._step_sent[node.id]`, postback node_id qua `FlowContext.currentNodeId`); `FlowGraphValidator` thêm rule stepped (`unknown_step`/`step_text_empty`/`step_media_empty`/`buttons_not_last` + tái dùng `button_edge_missing`/`interactive_unsupported`); FE `STEP_META`/`StepViewer`/`newStep`, `StepListEditor` + "+ Tạo bước", `SendMessageNode` render steps + handle/nút, prune edge dùng chung. Áp **cả Facebook lẫn Zalo** (gate buttons qua capability). Flow phẳng cũ chạy & validate y nguyên (migrate-on-open ở editor, KHÔNG migration dữ liệu). 634 messaging tests + pint + phpstan + FE typecheck/lint/build xanh.
>
> **GIỚI HẠN 2A (để 2B/2C):** mới chỉ node `send_message` là "stepped"; các node điều khiển `condition`/`wait_reply`/`ai_reply`/`send_comment_reply`/`end`/`trigger` vẫn là node phẳng riêng. Mỗi node tối đa **1 step `send_buttons` và phải là bước cuối** (chưa hỗ trợ wait giữa node nhiều lần). Chưa có hạ tầng biến/tag + scheduler (delay) — thuộc 2B.

**Phase 2B — Step giá trị cao + 2 hạ tầng:**
- Hạ tầng: kho biến/custom-field + tag khách (`customers` meta / bảng mới); bộ hẹn-giờ resume (bảng cursor + command scanner mỗi phút, idempotent jobId — mirror ChatbotX smart-delay).
- Step: **Delay** (theo giờ/random/đến mốc), **Hỏi-đáp/getUserData** (validate replyFormat, lưu biến, success/skip), **Set/Clear field**, **Add/Remove tag**, **Split A/B** (sticky theo khách).

**Phase 2C — Long tail:**
- **Gọi API/HTTP** (response→biến, success/failure), **AI step** (generate-text→biến, extract-data, analyze-image), **inbox actions** (chuyển người thật/assign/disable bot), **Card/Carousel** (gate capability), jump (`start_another_node`/`start_external_flow`), typing.

## 5. Trong / ngoài phạm vi

**Trong (toàn spec):** đại tu builder node-with-steps + step registry 2 phía + step editors/viewers + engine step-runner + migrate flow cũ + các step phase 2B/2C ưu tiên cho FB/Zalo.

**Ngoài:** step đặc thù Meta/bên-thứ-3 không có tương đương FB/Zalo (Google Sheets, ESP mail, WhatsApp/Messenger-only template, persistent menu, landing page, broadcast/sequence trong flow). Không đổi FlowMatcher/FlowRun core (chỉ mở rộng). Không đụng connector ngoài DTO/capability.

## 6. Rủi ro

- **Refactor lớn + migrate flow cũ** — phải có migration an toàn (flat→1-step) + test đảm bảo flow cũ chạy nguyên. Cân nhắc giữ song song renderer cũ tới khi 2A xanh.
- Hạ tầng biến/tag + scheduler là phụ thuộc cứng của 2B — làm trước trong 2B.
- Prod baked image cần redeploy ([[prod-ops-ssh-and-deploy]]); test bằng baseline hiện có (chưa xanh toàn cục — [[test-verify-baseline]]).
