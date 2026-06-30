# Flow Builder node-with-steps — Phase 2A Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development. Steps use `- [ ]`.

**Goal:** Một node "Gửi tin" trong Flow Builder chứa **danh sách bước (steps) có thứ tự** — gửi văn bản / ảnh-video-file / nút bấm — thêm từ menu "+ Tạo bước" phân nhóm (kiểu ChatbotX), render thành thẻ xếp chồng. Áp cho **cả Facebook lẫn Zalo OA**. **Additive & non-breaking**: flow phẳng cũ chạy y nguyên.

**Architecture:** Thêm trục **Step** song song trục Node hiện có. `node.data.steps?: Step[]` là tùy chọn. BE: `StepExecutor` interface + `StepExecutorRegistry` (mirror `NodeExecutor`); `SendMessageNodeExecutor` nếu thấy `steps[]` thì duyệt steps (idempotency theo `node.id + step index`), không có thì giữ hành vi cũ. FE: `stepRegistry` (label/icon/group/defaultFn/editor/viewer); node editor hiện danh sách step sortable + dropdown "+ Tạo bước"; node card render mini-card mỗi step + handle nút.

**Tech Stack:** Laravel 11 (PHP 8.3), `CMBcoreSeller\Modules\Messaging`; React 18 + AntD + @xyflow/react; TanStack Query. Commands chạy từ `app/`.

## Global Constraints

- **Non-breaking:** node `send_message` KHÔNG có `steps[]` (flow cũ) phải chạy & validate y như hiện tại. Không migration đổi dữ liệu cũ.
- **Provider-agnostic:** step gửi nút gate qua `InteractiveMessagingConnector::supports('outbound.interactive')`, KHÔNG `instanceof` tên sàn. Áp cho cả facebook_page & zalo_oa.
- UI: icon dùng `@ant-design/icons` (không emoji); option nhỏ dùng Radio/Segmented không Select; tiếng Việt cho chuỗi người dùng.
- Idempotency: re-run node đã gửi không gửi lại (cursor trong `FlowRun.context`).
- Test baseline: `php artisan test tests/Feature/Messaging tests/Unit/Messaging` phải xanh; pint+phpstan(level 5)+`npm run typecheck/lint/build` xanh.
- Mọi chuỗi step type ổn định (lưu trong jsonb) — đặt const, không đổi về sau.

**Step types Phase 2A:** `send_text` `{text:string}` · `send_media` `{kind:'image'|'video'|'file', attachment:FlowAttachment}` · `send_buttons` `{text:string, buttons:FlowButton[]}` (mỗi postback button = 1 handle = button.id; chỉ node CHỨA bước này mới có handle nhánh — Phase 2A giới hạn **tối đa 1 bước send_buttons mỗi node và phải là bước cuối**).

---

### Task 1: BE — Step contracts + registry + DTO

**Files:**
- Create: `app/app/Modules/Messaging/Services/Flows/Steps/StepExecutor.php`
- Create: `app/app/Modules/Messaging/Services/Flows/Steps/StepResult.php`
- Create: `app/app/Modules/Messaging/Services/Flows/Steps/StepExecutorRegistry.php`
- Create: `app/app/Modules/Messaging/Services/Flows/Steps/FlowStep.php`
- Test: `app/tests/Unit/Messaging/Flows/StepExecutorRegistryTest.php`

**Interfaces:**
- Produces:
  - `interface StepExecutor { public function type(): string; public function execute(FlowStep $step, FlowContext $ctx): StepResult; }`
  - `final class FlowStep { public function __construct(public string $id, public string $type, public array $config) {} public static function fromArray(array $a): self; }` (config = phần còn lại của step trừ id/type).
  - `final class StepResult` factories: `done()` (bước xong, sang bước kế), `wait(?string $handle=null)` (dừng node — chờ postback/inbound), `fail(string $error)`. Getters `isWait/isFail/handle/error`.
  - `class StepExecutorRegistry { has(string $type):bool; for(string $type):StepExecutor; register(string $type,string $class):void; }` resolve qua container (mirror `NodeExecutorRegistry`).

- [ ] **Step 1: Test** — `StepExecutorRegistryTest`: register một fake StepExecutor type `noop`, `has('noop')` true, `for('noop')` trả instance, `has('x')` false; `for('x')` ném `InvalidArgumentException`. `StepResult::wait('h')->handle()==='h'`, `done()->isWait()===false`.

```php
public function test_registry_resolves_and_unknown_throws(): void
{
    $r = new StepExecutorRegistry($this->app);
    $r->register('noop', NoopStep::class);
    $this->assertTrue($r->has('noop'));
    $this->assertInstanceOf(StepExecutor::class, $r->for('noop'));
    $this->assertFalse($r->has('missing'));
    $this->expectException(\InvalidArgumentException::class);
    $r->for('missing');
}
```
(Định nghĩa `NoopStep implements StepExecutor` ngay trong file test trả `StepResult::done()`.)

- [ ] **Step 2: Run test → FAIL** (`php artisan test --filter=StepExecutorRegistryTest`) — class not found.
- [ ] **Step 3: Implement** 4 class. Mirror `app/app/Modules/Messaging/Services/Flows/Nodes/NodeExecutorRegistry.php` (đọc nó để copy cấu trúc container + chữ ký). `FlowStep::fromArray`: `id=$a['id']`, `type=$a['type']`, `config = Arr::except($a, ['id','type'])`.
- [ ] **Step 4: Run test → PASS.**
- [ ] **Step 5: Commit** `feat(flow): step executor contracts + registry (node-with-steps nền tảng)`.

---

### Task 2: BE — 3 step executors (send_text, send_media, send_buttons)

**Files:**
- Create: `app/app/Modules/Messaging/Services/Flows/Steps/SendTextStep.php`
- Create: `app/app/Modules/Messaging/Services/Flows/Steps/SendMediaStep.php`
- Create: `app/app/Modules/Messaging/Services/Flows/Steps/SendButtonsStep.php`
- Modify: `app/app/Modules/Messaging/MessagingServiceProvider.php` (đăng ký registry + 3 step)
- Test: `app/tests/Feature/Messaging/Flows/StepExecutorsTest.php`

**Interfaces:**
- Consumes: `StepExecutor`/`StepResult`/`FlowStep`/`FlowContext`. Tái dùng cách gửi của các node executor hiện có — ĐỌC `Services/Flows/Nodes/SendMessageNodeExecutor.php` (gửi text + media attachments) và `Services/Flows/Nodes/SendInteractiveNodeExecutor.php` (gửi nút + capability gate + `FlowPostbackPayload`).
- `SendTextStep` type `send_text`: tạo outbound Message text `config['text']` cho `ctx->conversation`, trả `StepResult::done()`.
- `SendMediaStep` type `send_media`: gửi attachment (`config['attachment']`, `config['kind']`) như SendMessageNodeExecutor làm với media → `done()`.
- `SendButtonsStep` type `send_buttons`: capability gate `InteractiveMessagingConnector` + `supports('outbound.interactive')`; không hỗ trợ → `fail('interactive_unsupported')`. Gửi text+buttons; payload postback encode `FlowPostbackPayload` (CẦN node_id + step ⇒ xem Task 3 cho việc truyền node_id/handle). Trả `StepResult::wait()` (handle do node executor quyết định theo button như cũ).

**Lưu ý gửi tin:** KHÔNG nhân bản logic — trích phần gửi dùng chung nếu cần, hoặc gọi lại service hiện có. Giữ idempotency ở Task 3 (node executor truyền cursor), step executor chỉ "gửi 1 lần khi được gọi".

- [ ] **Step 1: Test** `StepExecutorsTest` (dùng `Queue::fake`/connector fake như test node hiện có — đọc `tests/Feature/Messaging/` để theo mẫu fake connector): 
  - `send_text` tạo 1 Message outbound body đúng, trả done.
  - `send_buttons` trên provider KHÔNG interactive → `fail('interactive_unsupported')`, không tạo message.
  - `send_buttons` trên provider interactive (fake supports) → tạo message + `isWait()` true.
- [ ] **Step 2: Run → FAIL.**
- [ ] **Step 3: Implement** 3 step + đăng ký trong `MessagingServiceProvider` (bind `StepExecutorRegistry` singleton, `register('send_text',...)` v.v. — mirror khối `NodeExecutorRegistry` ở `MessagingServiceProvider.php:80-89`).
- [ ] **Step 4: Run → PASS.**
- [ ] **Step 5: Commit** `feat(flow): step executors send_text/send_media/send_buttons + đăng ký`.

---

### Task 3: BE — SendMessageNodeExecutor duyệt steps[] (additive, idempotent)

**Files:**
- Modify: `app/app/Modules/Messaging/Services/Flows/Nodes/SendMessageNodeExecutor.php`
- Test: `app/tests/Feature/Messaging/Flows/SteppedNodeExecutionTest.php`

**Interfaces:**
- Consumes `StepExecutorRegistry` (inject). 
- Hành vi: nếu `node->data['steps']` là mảng không rỗng ⇒ duyệt từng step theo thứ tự:
  - Idempotency: cursor `ctx->run->context['_step_sent'][node.id]` = số step đã gửi xong; bỏ qua step có index < cursor.
  - Mỗi step: `registry->for(type)->execute(...)`. `isFail` ⇒ trả `NodeResult::fail`. `isWait` ⇒ lưu cursor (đã gửi tới step này) + trả `NodeResult::wait()` nếu là chờ inbound, hoặc với send_buttons trả wait và để postback resume (button handle). `done` ⇒ tăng cursor, tiếp.
  - Hết steps ⇒ `NodeResult::advance(null)` (đi tiếp theo edge "Tiếp tục").
- Nếu KHÔNG có `steps[]` ⇒ chạy nhánh cũ y nguyên (đọc code hiện tại, giữ).
- send_buttons giữa node: Phase 2A ép là step cuối ⇒ wait + handle theo button (postback payload encode node_id; resume tại node, theo edge sourceHandle=button.id như cơ chế hiện có). Cập nhật `FlowPostbackPayload` nếu cần node_id (đã có) — KHÔNG cần step index vì buttons là bước cuối.

- [ ] **Step 1: Test** `SteppedNodeExecutionTest`:
  - Node có steps `[send_text, send_text]` ⇒ gửi 2 message, trả advance(null).
  - Chạy lại cùng run sau khi cursor=2 ⇒ KHÔNG gửi lại (idempotent).
  - Node có `[send_text, send_buttons]` (provider interactive) ⇒ gửi 2 message, trả wait.
  - Node KHÔNG steps (data cũ: text trực tiếp) ⇒ hành vi cũ giữ nguyên (gửi 1 message).
- [ ] **Step 2: Run → FAIL.**
- [ ] **Step 3: Implement** thêm nhánh steps vào executor.
- [ ] **Step 4: Run → PASS** (+ chạy `tests/Feature/Messaging/Flows` cũ để chắc non-breaking).
- [ ] **Step 5: Commit** `feat(flow): node send_message duyệt steps[] (idempotent, giữ nhánh cũ)`.

---

### Task 4: BE — Validator hỗ trợ stepped node

**Files:**
- Modify: `app/app/Modules/Messaging/Services/Flows/FlowGraphValidator.php`
- Test: `app/tests/Feature/Messaging/Flows/SteppedFlowValidationTest.php` (hoặc mở rộng test validator hiện có — đọc test hiện tại trước)

**Interfaces:**
- Với node `send_message` có `steps[]`:
  - mỗi step type phải đăng ký (`StepExecutorRegistry::has`) ⇒ else `unknown_step`.
  - `send_text` cần `text` không rỗng ⇒ `step_text_empty`.
  - `send_media` cần `attachment` ⇒ `step_media_empty`.
  - `send_buttons`: phải là step CUỐI (`buttons_not_last` nếu không); mỗi postback button có edge `sourceHandle===button.id` (tái dùng rule `button_edge_missing`); cần capability interactive (tái dùng rule `interactive_unsupported`); url button bỏ qua.
- Node không steps ⇒ rule cũ giữ nguyên.

- [ ] **Step 1: Test** các case: steps hợp lệ → no error; send_text rỗng → `step_text_empty`; send_buttons không phải cuối → `buttons_not_last`; thiếu edge cho button → `button_edge_missing`.
- [ ] **Step 2: Run → FAIL.**
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run → PASS** (+ test validator cũ xanh).
- [ ] **Step 5: Commit** `feat(flow): validator cho stepped send_message node`.

---

### Task 5: FE — Step registry + types

**Files:**
- Create: `app/resources/js/features/messaging/flow/steps.tsx` (registry: types, label/icon/group, defaultFn, viewer mini-card)
- Modify: `app/resources/js/lib/messagingFlows.tsx` (types `FlowStep`, `FlowStepType`; thêm `steps?: FlowStep[]` vào `FlowNodeData`)

**Interfaces:**
- `type FlowStepType = 'send_text'|'send_media'|'send_buttons';`
- `interface FlowStep { id:string; type:FlowStepType; text?:string; kind?:'image'|'video'|'file'; attachment?:FlowAttachment; buttons?:FlowButton[]; }`
- `STEP_META: Record<FlowStepType, { label:string; group:'send'; icon:ReactNode; defaultFn():Omit<FlowStep,'id'>; }>` — icon AntD.
- `StepViewer({step}: {step:FlowStep})` mini-card: text → đoạn text; media → tag "Ảnh/Video/File"; buttons → text + chip mỗi nút.

- [ ] **Step 1:** thêm types vào `messagingFlows.tsx`.
- [ ] **Step 2:** tạo `steps.tsx` với `STEP_META` + `StepViewer` + `newStep(type)` (gắn id ngẫu nhiên `crypto.randomUUID()`).
- [ ] **Step 3:** `npm run typecheck` xanh.
- [ ] **Step 4: Commit** `feat(flow-ui): step registry + types (FE)`.

---

### Task 6: FE — Node editor: danh sách step sortable + "+ Tạo bước"

**Files:**
- Modify: `app/resources/js/features/messaging/flow/NodeConfigDrawer.tsx` (nhánh `send_message`: nếu node có/đổi sang steps ⇒ render trình quản lý step)
- Có thể tạo: `app/resources/js/features/messaging/flow/StepListEditor.tsx`

**Interfaces:**
- Khi mở config node `send_message`: hiển thị danh sách `steps` (kéo sắp xếp bằng AntD `List` + nút lên/xuống hoặc dnd; tối thiểu: nút ↑/↓ + xoá để tránh phụ thuộc dnd-kit), mỗi step có editor riêng (text area / upload media / buttons Form.List tái dùng `ButtonRow` hiện có). 
- Nút **"+ Tạo bước"** = `Dropdown` menu phân nhóm "Gửi tin" → các step trong `STEP_META`. Chọn ⇒ `newStep(type)` append.
- **Migrate-on-open (không phá cũ):** nếu node chưa có `steps` nhưng có `data.text`/`data.attachments`/`data.buttons` (kiểu cũ) ⇒ khởi tạo `steps` từ đó để người dùng sửa ở dạng step; khi lưu node ghi cả `steps` (engine ưu tiên steps). Giữ `data` cũ để rollback an toàn (không xoá field cũ).
- Ràng buộc UI: chỉ cho 1 step `send_buttons` và đặt nó xuống cuối (disable thêm nếu đã có; cảnh báo nếu không phải cuối).

- [ ] **Step 1:** Implement `StepListEditor` + tích hợp vào `NodeConfigDrawer` cho `send_message`.
- [ ] **Step 2:** `npm run typecheck && npm run lint` xanh.
- [ ] **Step 3: Commit** `feat(flow-ui): node editor danh sách step + menu Tạo bước`.

---

### Task 7: FE — Node card render steps + handle nút theo step

**Files:**
- Modify: `app/resources/js/features/messaging/flow/nodes.tsx` (`SendMessageNode`: nếu `data.steps` ⇒ render `StepViewer` từng step; handle nút lấy từ step `send_buttons` cuối)

**Interfaces:**
- `SendMessageNode`: nếu có `steps` ⇒ stacked mini-cards (StepViewer) thay cho preview text đơn; nếu step cuối là `send_buttons` ⇒ render 1 `<Handle source>` mỗi postback button keyed `b.id` (tái dùng cách `SendButtonsNode` hiện làm) + handle "Tiếp tục" nếu cần. Không steps ⇒ render như cũ.
- Đồng bộ prune edge: khi buttons trong step đổi, prune edge `sourceHandle` không khớp (tái dùng logic prune ở `MessagingFlowEditorPage`).

- [ ] **Step 1:** Implement render steps + handles.
- [ ] **Step 2:** `npm run typecheck && npm run lint && npm run build` xanh.
- [ ] **Step 3: Commit** `feat(flow-ui): node card hiển thị steps + handle nút theo bước`.

---

### Task 8: Kiểm thử tổng + tài liệu

**Files:**
- Modify: `docs/specs/0040-flow-builder-node-with-steps.md` (đánh dấu Phase 2A: scope đã làm = send node stepped; ghi giới hạn: control nodes condition/wait/ai/comment vẫn là node riêng — để Phase 2B/2C)
- Modify: `docs/05-api/endpoints.md` nếu có đổi (không đổi endpoint — chỉ shape graph; ghi chú `node.data.steps[]`)

- [ ] **Step 1:** Chạy `php artisan test tests/Feature/Messaging tests/Unit/Messaging` + `vendor/bin/pint --test` + `vendor/bin/phpstan analyse` + `npm run typecheck && npm run lint && npm run build` — tất cả xanh.
- [ ] **Step 2:** Cập nhật doc.
- [ ] **Step 3: Commit** `docs(flow): Phase 2A node-with-steps (send node) đã làm + giới hạn`.
