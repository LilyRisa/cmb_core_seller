# Automation Flow Builder — S1 Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the backend foundation of a visual flow/scenario engine for Facebook: store flows as a node graph and run them per-conversation (start on inbound message/comment, execute send/condition/wait/end nodes, resume on next reply), idempotently — no UI, no postback yet.

**Architecture:** New sub-domain `Flows` inside the existing **Messaging** module. A flow is a node graph (`automation_flows.graph` jsonb). `FlowEngine` walks the graph and persists per-conversation state in `flow_runs`. Node behaviour is pluggable via a **`NodeExecutorRegistry`** (one class per node type — adding a node type = new class + one `register()` call, never editing the engine). Sends reuse the existing `OutboundMessageService` (DM) and `CommentReplyService` (comment), so audit + the 24h `OutboundWindowGuard` run unchanged. Core never references "facebook" — provider differences stay in connectors (capability map). Spec: `docs/superpowers/specs/2026-05-28-facebook-automation-flow-builder-design.md`.

**Tech Stack:** Laravel 11, PHP 8.3, PostgreSQL (jsonb), PHPUnit. Namespace `CMBcoreSeller\Modules\Messaging\`. All commands run from `app/`.

---

## Conventions (read once)

- **All commands run from `app/`.** Namespace `CMBcoreSeller\` → `app/app/`.
- Every business table has `tenant_id`; models use `BelongsToTenant` (global scope). Engine runs **without** auth tenant (listener/job) → query with `withoutGlobalScope(TenantScope::class)` + explicit `where('tenant_id', …)`, exactly like `AutoReplyEngine`.
- Tests: Feature in `app/tests/Feature/Messaging/Flows/`, Unit in `app/tests/Unit/Messaging/Flows/`. Run a single test: `php artisan test --filter=ClassName`.
- **Test DB = SQLite `:memory:`, `QUEUE_CONNECTION=sync`** (`app/phpunit.xml`). Keep migrations + queries DB-agnostic (no Postgres-only SQL in `Flows`). In tests that trigger a send, call `Queue::fake()` so the real `SendMessage`/`SendCommentReply` job doesn't try to reach Facebook. (SQLite ≥3.8 supports the partial unique index in Task 2 — but run the test to confirm the migration applies.)
- Quality gate before final commit: `vendor/bin/pint`, `vendor/bin/phpstan analyse`, `php artisan test`.
- **No hardcoded provider names** anywhere in `Flows`. **No mock/sample data** in shipped code.

---

## File Structure

**Create (backend, under `app/app/Modules/Messaging/`):**
- `Database/Migrations/2026_05_29_100001_create_automation_flows_table.php` — flows table.
- `Database/Migrations/2026_05_29_100002_create_flow_runs_table.php` — per-conversation run state.
- `Models/AutomationFlow.php` — flow model (trigger/status constants, jsonb casts).
- `Models/FlowRun.php` — run model (status constants, context cast).
- `Services/Flows/Graph/FlowGraph.php` — parse `graph` jsonb; find trigger node; next node by edge handle.
- `Services/Flows/Graph/FlowNode.php` — immutable node value object (`id`, `type`, `data`).
- `Services/Flows/Nodes/NodeExecutor.php` — interface (`type()`, `execute()`).
- `Services/Flows/Nodes/NodeResult.php` — result value object (advance/wait/end/fail).
- `Services/Flows/Nodes/NodeExecutorRegistry.php` — type → executor resolver.
- `Services/Flows/Nodes/TriggerNodeExecutor.php`
- `Services/Flows/Nodes/SendMessageNodeExecutor.php`
- `Services/Flows/Nodes/SendCommentReplyNodeExecutor.php`
- `Services/Flows/Nodes/ConditionNodeExecutor.php`
- `Services/Flows/Nodes/WaitReplyNodeExecutor.php`
- `Services/Flows/Nodes/EndNodeExecutor.php`
- `Services/Flows/FlowContext.php` — per-execution context (conversation, inbound body, run).
- `Services/Flows/FlowEngine.php` — start/advance/resume.
- `Services/Flows/FlowMatcher.php` — find active flows matching a trigger for a conversation.
- `Listeners/StartFlowOnInbound.php` — `MessageReceived` → engine.
- `Listeners/StartFlowOnComment.php` — `CommentReceived` → engine.

**Modify:**
- `MessagingServiceProvider.php` — register the executor registry (singleton) + wire the two listeners.

**Tests (create):**
- `app/tests/Unit/Messaging/Flows/FlowGraphTest.php`
- `app/tests/Unit/Messaging/Flows/NodeExecutorRegistryTest.php`
- `app/tests/Unit/Messaging/Flows/ConditionNodeExecutorTest.php`
- `app/tests/Feature/Messaging/Flows/FlowEngineTest.php`
- `app/tests/Feature/Messaging/Flows/FlowListenersTest.php`

---

## Node graph data shape (locked here; the S3 canvas will emit exactly this)

```jsonc
{
  "nodes": [
    { "id": "n1", "type": "trigger",            "data": {} },
    { "id": "n2", "type": "send_message",        "data": { "text": "Xin chào 👋" } },
    { "id": "n3", "type": "wait_reply",          "data": {} },
    { "id": "n4", "type": "condition",           "data": { "keywords": ["giá", "price"], "match": "any" } },
    { "id": "n5", "type": "send_message",        "data": { "text": "Giá là ..." } },
    { "id": "n6", "type": "send_comment_reply",  "data": { "text": "Cảm ơn bạn!", "target": { "public": true, "private": false } } },
    { "id": "n7", "type": "end",                 "data": {} }
  ],
  "edges": [
    { "id": "e1", "source": "n1", "target": "n2", "sourceHandle": null },
    { "id": "e2", "source": "n2", "target": "n3", "sourceHandle": null },
    { "id": "e3", "source": "n3", "target": "n4", "sourceHandle": null },
    { "id": "e4", "source": "n4", "target": "n5", "sourceHandle": "match" },
    { "id": "e5", "source": "n4", "target": "n7", "sourceHandle": "no_match" }
  ]
}
```

`sourceHandle` selects which outgoing edge a branching node takes (`condition` → `"match"`/`"no_match"`). Linear nodes use `null`.

---

## Task 1: Migration — `automation_flows`

**Files:**
- Create: `app/app/Modules/Messaging/Database/Migrations/2026_05_29_100001_create_automation_flows_table.php`

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `automation_flows` — kịch bản tự động dạng đồ thị node (Flow Builder S1).
 * `graph` jsonb do canvas (S3) sinh; người dùng không sửa JSON tay.
 * Spec: docs/superpowers/specs/2026-05-28-facebook-automation-flow-builder-design.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('name');
            $table->string('provider', 32)->default('facebook_page');
            $table->string('status', 16)->default('draft');        // draft|active|paused|archived
            $table->string('trigger_type', 32);                    // comment_on_post|comment_any|inbox_first_message|inbox_keyword|inbox_any
            $table->json('trigger_config')->nullable();            // { post_ids:[], keywords:[], match:'any|all' }
            $table->json('graph')->nullable();                     // { nodes:[], edges:[] }
            $table->unsignedInteger('version')->default(1);
            $table->boolean('enabled')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'provider', 'status', 'enabled']);
            $table->index(['tenant_id', 'trigger_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_flows');
    }
};
```

- [ ] **Step 2: Run the migration**

Run: `php artisan migrate`
Expected: `automation_flows` table created (no error).

- [ ] **Step 3: Commit**

```bash
git add app/Modules/Messaging/Database/Migrations/2026_05_29_100001_create_automation_flows_table.php
git commit -m "feat(messaging): automation_flows table (flow builder S1)"
```

---

## Task 2: Migration — `flow_runs`

**Files:**
- Create: `app/app/Modules/Messaging/Database/Migrations/2026_05_29_100002_create_flow_runs_table.php`

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `flow_runs` — state máy thực thi flow THEO TỪNG hội thoại.
 * Unique partial (flow_id, conversation_id) WHERE status IN ('active','waiting')
 * ⇒ một hội thoại chỉ có 1 run đang chạy / flow (chống double-enter, idempotent).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('flow_id');
            $table->foreignId('conversation_id');
            $table->string('current_node_id')->nullable();   // node đang chờ input; null khi vừa enter
            $table->string('status', 16)->default('active'); // active|waiting|completed|ended|failed
            $table->json('context')->nullable();             // biến thu thập + _sent[] (node đã gửi)
            $table->string('error')->nullable();
            $table->timestamp('entered_at')->nullable();
            $table->timestamp('last_advanced_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'conversation_id', 'status']);
        });

        // Postgres partial unique: chỉ 1 run active/waiting / (flow, conversation).
        DB::statement(
            "CREATE UNIQUE INDEX flow_runs_one_active_per_conv
             ON flow_runs (flow_id, conversation_id)
             WHERE status IN ('active','waiting')"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_runs');
    }
};
```

> Note: add `use Illuminate\Support\Facades\DB;` at the top alongside the other imports.

- [ ] **Step 2: Run the migration**

Run: `php artisan migrate`
Expected: `flow_runs` table + partial unique index created.

- [ ] **Step 3: Commit**

```bash
git add app/Modules/Messaging/Database/Migrations/2026_05_29_100002_create_flow_runs_table.php
git commit -m "feat(messaging): flow_runs state table with partial-unique active guard"
```

---

## Task 3: `AutomationFlow` model

**Files:**
- Create: `app/app/Modules/Messaging/Models/AutomationFlow.php`

- [ ] **Step 1: Write the model**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Kịch bản tự động (Flow Builder). `graph` = { nodes:[], edges:[] } do canvas sinh.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $provider
 * @property string $status
 * @property string $trigger_type
 * @property ?array $trigger_config
 * @property ?array $graph
 * @property int $version
 * @property bool $enabled
 */
class AutomationFlow extends Model
{
    use BelongsToTenant;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_ARCHIVED = 'archived';

    public const TRIGGER_COMMENT_ON_POST = 'comment_on_post';
    public const TRIGGER_COMMENT_ANY = 'comment_any';
    public const TRIGGER_INBOX_FIRST_MESSAGE = 'inbox_first_message';
    public const TRIGGER_INBOX_KEYWORD = 'inbox_keyword';
    public const TRIGGER_INBOX_ANY = 'inbox_any';

    protected $fillable = [
        'tenant_id', 'name', 'provider', 'status', 'trigger_type',
        'trigger_config', 'graph', 'version', 'enabled', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'trigger_config' => 'array',
            'graph' => 'array',
            'version' => 'integer',
            'enabled' => 'boolean',
        ];
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Modules/Messaging/Models/AutomationFlow.php
git commit -m "feat(messaging): AutomationFlow model"
```

---

## Task 4: `FlowRun` model

**Files:**
- Create: `app/app/Modules/Messaging/Models/FlowRun.php`

- [ ] **Step 1: Write the model**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * State máy chạy 1 flow trên 1 conversation. `context` giữ biến thu thập +
 * `_sent` (id node đã gửi, chống gửi lại khi advance lặp).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $flow_id
 * @property int $conversation_id
 * @property ?string $current_node_id
 * @property string $status
 * @property ?array $context
 * @property ?string $error
 */
class FlowRun extends Model
{
    use BelongsToTenant;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_WAITING = 'waiting';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ENDED = 'ended';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id', 'flow_id', 'conversation_id', 'current_node_id',
        'status', 'context', 'error', 'entered_at', 'last_advanced_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'entered_at' => 'datetime',
            'last_advanced_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Modules/Messaging/Models/FlowRun.php
git commit -m "feat(messaging): FlowRun model"
```

---

## Task 5: `FlowGraph` + `FlowNode` value objects

**Files:**
- Create: `app/app/Modules/Messaging/Services/Flows/Graph/FlowNode.php`
- Create: `app/app/Modules/Messaging/Services/Flows/Graph/FlowGraph.php`
- Test: `app/tests/Unit/Messaging/Flows/FlowGraphTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Messaging\Flows;

use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowGraph;
use PHPUnit\Framework\TestCase;

class FlowGraphTest extends TestCase
{
    private function graph(): FlowGraph
    {
        return new FlowGraph([
            'nodes' => [
                ['id' => 'n1', 'type' => 'trigger', 'data' => []],
                ['id' => 'n2', 'type' => 'send_message', 'data' => ['text' => 'hi']],
                ['id' => 'n3', 'type' => 'condition', 'data' => ['keywords' => ['x']]],
                ['id' => 'n4', 'type' => 'end', 'data' => []],
            ],
            'edges' => [
                ['source' => 'n1', 'target' => 'n2', 'sourceHandle' => null],
                ['source' => 'n3', 'target' => 'n4', 'sourceHandle' => 'match'],
                ['source' => 'n3', 'target' => 'n2', 'sourceHandle' => 'no_match'],
            ],
        ]);
    }

    public function test_finds_trigger_node(): void
    {
        $this->assertSame('n1', $this->graph()->triggerNode()?->id);
    }

    public function test_next_node_follows_default_edge(): void
    {
        $this->assertSame('n2', $this->graph()->nextNodeId('n1', null));
    }

    public function test_next_node_follows_named_handle(): void
    {
        $this->assertSame('n4', $this->graph()->nextNodeId('n3', 'match'));
        $this->assertSame('n2', $this->graph()->nextNodeId('n3', 'no_match'));
    }

    public function test_next_node_null_when_no_edge(): void
    {
        $this->assertNull($this->graph()->nextNodeId('n4', null));
    }

    public function test_node_lookup_by_id_returns_type_and_data(): void
    {
        $node = $this->graph()->node('n2');
        $this->assertSame('send_message', $node?->type);
        $this->assertSame('hi', $node?->data['text']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FlowGraphTest`
Expected: FAIL — class `FlowGraph` not found.

- [ ] **Step 3: Write `FlowNode`**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Graph;

/** Node bất biến trong đồ thị flow. */
final class FlowNode
{
    /** @param array<string,mixed> $data */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly array $data,
    ) {}
}
```

- [ ] **Step 4: Write `FlowGraph`**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Graph;

/**
 * Đọc cấu trúc graph jsonb (nodes/edges) → tra node + tìm node kế tiếp theo handle.
 * KHÔNG có logic nghiệp vụ — chỉ điều hướng đồ thị.
 */
final class FlowGraph
{
    /** @var array<string,FlowNode> */
    private array $nodes = [];

    /** @var list<array{source:string,target:string,sourceHandle:?string}> */
    private array $edges = [];

    /** @param array<string,mixed> $graph */
    public function __construct(array $graph)
    {
        foreach ((array) ($graph['nodes'] ?? []) as $n) {
            if (! isset($n['id'], $n['type'])) {
                continue;
            }
            $this->nodes[(string) $n['id']] = new FlowNode(
                id: (string) $n['id'],
                type: (string) $n['type'],
                data: (array) ($n['data'] ?? []),
            );
        }
        foreach ((array) ($graph['edges'] ?? []) as $e) {
            if (! isset($e['source'], $e['target'])) {
                continue;
            }
            $this->edges[] = [
                'source' => (string) $e['source'],
                'target' => (string) $e['target'],
                'sourceHandle' => isset($e['sourceHandle']) ? (string) $e['sourceHandle'] : null,
            ];
        }
    }

    public function node(string $id): ?FlowNode
    {
        return $this->nodes[$id] ?? null;
    }

    public function triggerNode(): ?FlowNode
    {
        foreach ($this->nodes as $node) {
            if ($node->type === 'trigger') {
                return $node;
            }
        }

        return null;
    }

    /**
     * Node đích đi từ `$fromId` qua edge có `sourceHandle === $handle`.
     * Linear node: $handle = null. Trả null nếu không có edge khớp.
     */
    public function nextNodeId(string $fromId, ?string $handle = null): ?string
    {
        foreach ($this->edges as $edge) {
            if ($edge['source'] === $fromId && $edge['sourceHandle'] === $handle) {
                return $edge['target'];
            }
        }

        return null;
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=FlowGraphTest`
Expected: PASS (5 tests).

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Messaging/Services/Flows/Graph/ app/tests/Unit/Messaging/Flows/FlowGraphTest.php
git commit -m "feat(messaging): FlowGraph/FlowNode graph navigation + tests"
```

---

## Task 6: Node executor framework (interface + result + registry)

**Files:**
- Create: `app/app/Modules/Messaging/Services/Flows/Nodes/NodeResult.php`
- Create: `app/app/Modules/Messaging/Services/Flows/FlowContext.php`
- Create: `app/app/Modules/Messaging/Services/Flows/Nodes/NodeExecutor.php`
- Create: `app/app/Modules/Messaging/Services/Flows/Nodes/NodeExecutorRegistry.php`
- Test: `app/tests/Unit/Messaging/Flows/NodeExecutorRegistryTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Messaging\Flows;

use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\NodeExecutorRegistry;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\NodeResult;
use RuntimeException;
use Tests\TestCase;

class NodeExecutorRegistryTest extends TestCase
{
    public function test_resolves_registered_executor_by_type(): void
    {
        $registry = new NodeExecutorRegistry();
        $registry->register('end', \CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\EndNodeExecutor::class);

        $this->assertTrue($registry->has('end'));
        $this->assertSame('end', $registry->for('end')->type());
    }

    public function test_unknown_type_throws(): void
    {
        $this->expectException(RuntimeException::class);
        (new NodeExecutorRegistry())->for('does_not_exist');
    }

    public function test_result_factories(): void
    {
        $this->assertTrue(NodeResult::wait()->isWait());
        $this->assertTrue(NodeResult::end()->isEnd());
        $this->assertSame('match', NodeResult::advance('match')->handle);
        $this->assertTrue(NodeResult::advance()->isAdvance());
        $this->assertSame('boom', NodeResult::fail('boom')->error);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=NodeExecutorRegistryTest`
Expected: FAIL — classes not found.

- [ ] **Step 3: Write `NodeResult`**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

/** Kết quả chạy 1 node: đi tiếp (theo handle) | chờ input | kết thúc | lỗi. */
final class NodeResult
{
    private function __construct(
        public readonly string $kind,      // advance|wait|end|fail
        public readonly ?string $handle = null,
        public readonly ?string $error = null,
    ) {}

    public static function advance(?string $handle = null): self
    {
        return new self('advance', handle: $handle);
    }

    public static function wait(): self
    {
        return new self('wait');
    }

    public static function end(): self
    {
        return new self('end');
    }

    public static function fail(string $error): self
    {
        return new self('fail', error: $error);
    }

    public function isAdvance(): bool
    {
        return $this->kind === 'advance';
    }

    public function isWait(): bool
    {
        return $this->kind === 'wait';
    }

    public function isEnd(): bool
    {
        return $this->kind === 'end';
    }

    public function isFail(): bool
    {
        return $this->kind === 'fail';
    }
}
```

- [ ] **Step 4: Write `FlowContext`**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows;

use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;

/** Dữ liệu 1 lần chạy node: hội thoại, run, nội dung inbound vừa nhận (nếu có). */
final class FlowContext
{
    public function __construct(
        public readonly Conversation $conversation,
        public readonly FlowRun $run,
        public readonly ?string $inboundBody = null,
    ) {}
}
```

- [ ] **Step 5: Write `NodeExecutor` interface**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;

/**
 * Một loại node = một executor. Thêm loại node mới = thêm class implement
 * interface này + 1 dòng register — KHÔNG sửa FlowEngine (mở rộng không hardcode).
 */
interface NodeExecutor
{
    /** Mã loại node, khớp `node.type` trong graph. */
    public function type(): string;

    public function execute(FlowNode $node, FlowContext $ctx): NodeResult;
}
```

- [ ] **Step 6: Write `NodeExecutorRegistry`**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use Illuminate\Contracts\Container\Container;
use RuntimeException;

/**
 * Map `node.type` → class executor. Resolve qua container (executor có thể inject
 * service như OutboundMessageService). Đăng ký ở MessagingServiceProvider.
 */
class NodeExecutorRegistry
{
    /** @var array<string,class-string<NodeExecutor>> */
    private array $map = [];

    public function __construct(private ?Container $container = null) {}

    /** @param class-string<NodeExecutor> $executorClass */
    public function register(string $type, string $executorClass): void
    {
        $this->map[$type] = $executorClass;
    }

    public function has(string $type): bool
    {
        return isset($this->map[$type]);
    }

    public function for(string $type): NodeExecutor
    {
        if (! isset($this->map[$type])) {
            throw new RuntimeException("No NodeExecutor registered for node type [{$type}].");
        }
        $class = $this->map[$type];

        return $this->container ? $this->container->make($class) : new $class();
    }
}
```

- [ ] **Step 7: Run test to verify it passes** (also requires `EndNodeExecutor` from Task 7 — write Task 7 Step for End first, or temporarily skip the registry-resolves test). To keep this task self-contained, change the test's `register('end', ...)` target to an inline anonymous class is NOT allowed (no placeholders). Instead, run only the result-factory test now:

Run: `php artisan test --filter=NodeExecutorRegistryTest::test_result_factories`
Expected: PASS. (The two registry tests pass after Task 7 adds `EndNodeExecutor`.)

- [ ] **Step 8: Commit**

```bash
git add app/Modules/Messaging/Services/Flows/Nodes/NodeResult.php app/Modules/Messaging/Services/Flows/Nodes/NodeExecutor.php app/Modules/Messaging/Services/Flows/Nodes/NodeExecutorRegistry.php app/Modules/Messaging/Services/Flows/FlowContext.php app/tests/Unit/Messaging/Flows/NodeExecutorRegistryTest.php
git commit -m "feat(messaging): node executor framework (interface, result, registry)"
```

---

## Task 7: Concrete node executors

**Files:**
- Create: `app/app/Modules/Messaging/Services/Flows/Nodes/TriggerNodeExecutor.php`
- Create: `app/app/Modules/Messaging/Services/Flows/Nodes/EndNodeExecutor.php`
- Create: `app/app/Modules/Messaging/Services/Flows/Nodes/WaitReplyNodeExecutor.php`
- Create: `app/app/Modules/Messaging/Services/Flows/Nodes/ConditionNodeExecutor.php`
- Create: `app/app/Modules/Messaging/Services/Flows/Nodes/SendMessageNodeExecutor.php`
- Create: `app/app/Modules/Messaging/Services/Flows/Nodes/SendCommentReplyNodeExecutor.php`
- Test: `app/tests/Unit/Messaging/Flows/ConditionNodeExecutorTest.php`

- [ ] **Step 1: Write `TriggerNodeExecutor`** (entry node — just advance)

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;

class TriggerNodeExecutor implements NodeExecutor
{
    public function type(): string
    {
        return 'trigger';
    }

    public function execute(FlowNode $node, FlowContext $ctx): NodeResult
    {
        return NodeResult::advance();
    }
}
```

- [ ] **Step 2: Write `EndNodeExecutor`**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;

class EndNodeExecutor implements NodeExecutor
{
    public function type(): string
    {
        return 'end';
    }

    public function execute(FlowNode $node, FlowContext $ctx): NodeResult
    {
        return NodeResult::end();
    }
}
```

- [ ] **Step 3: Write `WaitReplyNodeExecutor`** (pause until next inbound)

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;

class WaitReplyNodeExecutor implements NodeExecutor
{
    public function type(): string
    {
        return 'wait_reply';
    }

    public function execute(FlowNode $node, FlowContext $ctx): NodeResult
    {
        // Nếu đang resume (đã có inbound mới) ⇒ đi tiếp; nếu chưa ⇒ chờ.
        return $ctx->inboundBody !== null ? NodeResult::advance() : NodeResult::wait();
    }
}
```

- [ ] **Step 4: Write `ConditionNodeExecutor`** (keyword branch)

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;

/**
 * Rẽ nhánh theo từ khoá trong nội dung inbound. data:
 *   { keywords: string[], match: 'any'|'all' }
 * Edge handle: 'match' / 'no_match'.
 */
class ConditionNodeExecutor implements NodeExecutor
{
    public function type(): string
    {
        return 'condition';
    }

    public function execute(FlowNode $node, FlowContext $ctx): NodeResult
    {
        $keywords = array_values(array_filter(array_map(
            static fn ($k) => mb_strtolower(trim((string) $k)),
            (array) ($node->data['keywords'] ?? []),
        ), static fn (string $k) => $k !== ''));

        $haystack = mb_strtolower((string) ($ctx->inboundBody ?? ''));
        $matchMode = ($node->data['match'] ?? 'any') === 'all' ? 'all' : 'any';

        if ($keywords === []) {
            return NodeResult::advance('no_match');
        }

        $hits = array_filter($keywords, static fn (string $k) => str_contains($haystack, $k));
        $matched = $matchMode === 'all'
            ? count($hits) === count($keywords)
            : count($hits) > 0;

        return NodeResult::advance($matched ? 'match' : 'no_match');
    }
}
```

- [ ] **Step 5: Write `SendMessageNodeExecutor`** (DM text via existing service)

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;
use CMBcoreSeller\Modules\Messaging\Services\OutboundMessageService;

/**
 * Gửi tin DM (text). Tái dùng OutboundMessageService (audit + 24h window guard
 * chạy như tin NV gửi). Chống gửi lại: đánh dấu node id vào run.context._sent.
 */
class SendMessageNodeExecutor implements NodeExecutor
{
    public function __construct(private OutboundMessageService $outbound) {}

    public function type(): string
    {
        return 'send_message';
    }

    public function execute(FlowNode $node, FlowContext $ctx): NodeResult
    {
        $text = trim((string) ($node->data['text'] ?? ''));
        if ($text === '') {
            return NodeResult::advance(); // node rỗng ⇒ bỏ qua, không chặn luồng
        }

        $sent = (array) ($ctx->run->context['_sent'] ?? []);
        if (in_array($node->id, $sent, true)) {
            return NodeResult::advance(); // idempotent: đã gửi node này
        }

        $this->outbound->queueText($ctx->conversation, [
            'body' => $text,
            'sent_by_ai' => true,
            'kind' => 'text',
        ]);

        $sent[] = $node->id;
        $context = $ctx->run->context ?? [];
        $context['_sent'] = $sent;
        $ctx->run->update(['context' => $context]);

        return NodeResult::advance();
    }
}
```

- [ ] **Step 6: Write `SendCommentReplyNodeExecutor`** (public/private reply via existing service)

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use CMBcoreSeller\Modules\Messaging\Services\CommentReplyService;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;

/**
 * Trả lời comment (công khai / nhắn riêng) qua CommentReplyService. data:
 *   { text: string, target: { public: bool, private: bool } }
 */
class SendCommentReplyNodeExecutor implements NodeExecutor
{
    public function __construct(private CommentReplyService $commentReply) {}

    public function type(): string
    {
        return 'send_comment_reply';
    }

    public function execute(FlowNode $node, FlowContext $ctx): NodeResult
    {
        $text = trim((string) ($node->data['text'] ?? ''));
        if ($text === '') {
            return NodeResult::advance();
        }

        $sent = (array) ($ctx->run->context['_sent'] ?? []);
        if (in_array($node->id, $sent, true)) {
            return NodeResult::advance();
        }

        $target = (array) ($node->data['target'] ?? ['public' => true, 'private' => false]);
        $this->commentReply->dispatch($ctx->conversation, $text, [
            'public' => (bool) ($target['public'] ?? false),
            'private' => (bool) ($target['private'] ?? false),
        ]);

        $sent[] = $node->id;
        $context = $ctx->run->context ?? [];
        $context['_sent'] = $sent;
        $ctx->run->update(['context' => $context]);

        return NodeResult::advance();
    }
}
```

- [ ] **Step 7: Write the `ConditionNodeExecutor` test**

```php
<?php

namespace Tests\Unit\Messaging\Flows;

use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\ConditionNodeExecutor;
use PHPUnit\Framework\TestCase;

class ConditionNodeExecutorTest extends TestCase
{
    private function ctx(string $inbound): FlowContext
    {
        // Model không lưu DB ⇒ new instance đủ cho executor (chỉ đọc inboundBody).
        return new FlowContext(new Conversation(), new FlowRun(), $inbound);
    }

    public function test_any_match_returns_match_handle(): void
    {
        $node = new FlowNode('n', 'condition', ['keywords' => ['giá', 'price'], 'match' => 'any']);
        $res = (new ConditionNodeExecutor())->execute($node, $this->ctx('cho hỏi GIÁ bao nhiêu'));
        $this->assertSame('match', $res->handle);
    }

    public function test_no_match_returns_no_match_handle(): void
    {
        $node = new FlowNode('n', 'condition', ['keywords' => ['giá'], 'match' => 'any']);
        $res = (new ConditionNodeExecutor())->execute($node, $this->ctx('xin chào'));
        $this->assertSame('no_match', $res->handle);
    }

    public function test_all_mode_requires_every_keyword(): void
    {
        $node = new FlowNode('n', 'condition', ['keywords' => ['ship', 'phí'], 'match' => 'all']);
        $this->assertSame('no_match', (new ConditionNodeExecutor())->execute($node, $this->ctx('ship không'))->handle);
        $this->assertSame('match', (new ConditionNodeExecutor())->execute($node, $this->ctx('ship phí bao nhiêu'))->handle);
    }
}
```

- [ ] **Step 8: Run tests**

Run: `php artisan test --filter=ConditionNodeExecutorTest`
Expected: PASS (3 tests).
Run: `php artisan test --filter=NodeExecutorRegistryTest`
Expected: PASS (3 tests — `EndNodeExecutor` now exists).

- [ ] **Step 9: Commit**

```bash
git add app/Modules/Messaging/Services/Flows/Nodes/ app/tests/Unit/Messaging/Flows/ConditionNodeExecutorTest.php
git commit -m "feat(messaging): flow node executors (trigger/end/wait/condition/send/comment-reply)"
```

---

## Task 8: `FlowEngine` (start / advance / resume)

**Files:**
- Create: `app/app/Modules/Messaging/Services/Flows/FlowEngine.php`
- Test: `app/tests/Feature/Messaging/Flows/FlowEngineTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Messaging\Flows;

use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FlowEngineTest extends TestCase
{
    use RefreshDatabase;

    private function conv(): Conversation
    {
        return Conversation::create([
            'tenant_id' => 1, 'channel_account_id' => 1, 'provider' => 'facebook_page',
            'thread_type' => Conversation::THREAD_MESSAGE, 'external_conversation_id' => 'c1',
            'buyer_external_id' => 'b1', 'status' => 'open', 'message_count' => 1,
        ]);
    }

    private function flow(array $graph): AutomationFlow
    {
        return AutomationFlow::create([
            'tenant_id' => 1, 'name' => 'F', 'provider' => 'facebook_page',
            'status' => AutomationFlow::STATUS_ACTIVE,
            'trigger_type' => AutomationFlow::TRIGGER_INBOX_FIRST_MESSAGE,
            'graph' => $graph, 'enabled' => true,
        ]);
    }

    public function test_start_runs_send_then_wait_then_resume_branches(): void
    {
        Queue::fake(); // SendMessage job dispatched by OutboundMessageService

        $graph = [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => []],
                ['id' => 'hello', 'type' => 'send_message', 'data' => ['text' => 'Xin chào']],
                ['id' => 'w', 'type' => 'wait_reply', 'data' => []],
                ['id' => 'cond', 'type' => 'condition', 'data' => ['keywords' => ['giá'], 'match' => 'any']],
                ['id' => 'price', 'type' => 'send_message', 'data' => ['text' => 'Giá 100k']],
                ['id' => 'bye', 'type' => 'end', 'data' => []],
            ],
            'edges' => [
                ['source' => 't', 'target' => 'hello', 'sourceHandle' => null],
                ['source' => 'hello', 'target' => 'w', 'sourceHandle' => null],
                ['source' => 'w', 'target' => 'cond', 'sourceHandle' => null],
                ['source' => 'cond', 'target' => 'price', 'sourceHandle' => 'match'],
                ['source' => 'cond', 'target' => 'bye', 'sourceHandle' => 'no_match'],
            ],
        ];
        $conv = $this->conv();
        $flow = $this->flow($graph);
        $engine = app(FlowEngine::class);

        // Start: sends "Xin chào", stops at wait.
        $run = $engine->start($flow, $conv, inboundBody: 'hi');
        $this->assertSame(FlowRun::STATUS_WAITING, $run->fresh()->status);
        $this->assertSame('w', $run->fresh()->current_node_id);
        $this->assertSame(1, Message::where('conversation_id', $conv->id)->where('body', 'Xin chào')->count());

        // Resume with "giá ?" → condition match → sends "Giá 100k" → end.
        $engine->resume($run->fresh(), $conv->fresh(), inboundBody: 'cho hỏi giá');
        $this->assertSame(FlowRun::STATUS_COMPLETED, $run->fresh()->status);
        $this->assertSame(1, Message::where('conversation_id', $conv->id)->where('body', 'Giá 100k')->count());
    }

    public function test_start_is_idempotent_when_active_run_exists(): void
    {
        Queue::fake();
        $graph = [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => []],
                ['id' => 'w', 'type' => 'wait_reply', 'data' => []],
            ],
            'edges' => [['source' => 't', 'target' => 'w', 'sourceHandle' => null]],
        ];
        $conv = $this->conv();
        $flow = $this->flow($graph);
        $engine = app(FlowEngine::class);

        $engine->start($flow, $conv, inboundBody: 'hi');
        $engine->start($flow, $conv->fresh(), inboundBody: 'hi again'); // must NOT create a 2nd run

        $this->assertSame(1, FlowRun::where('flow_id', $flow->id)->where('conversation_id', $conv->id)->count());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FlowEngineTest`
Expected: FAIL — `FlowEngine` not found.

- [ ] **Step 3: Write `FlowEngine`**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows;

use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowGraph;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\NodeExecutorRegistry;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Chạy flow theo state máy. Bắt đầu khi trigger khớp; đi qua các node "tức thì"
 * cho tới khi gặp node "chờ" (wait_reply) ⇒ lưu state. Resume khi có inbound kế.
 *
 * Idempotent: flow_runs unique partial (flow, conversation) WHERE active/waiting ⇒
 * insert đụng = đã có run ⇒ bỏ qua (không double-enter). Chạy ngoài auth tenant
 * (listener) ⇒ tenant lấy từ conversation.
 *
 * MAX_STEPS chống vòng lặp đồ thị (node trỏ vòng lại).
 */
class FlowEngine
{
    private const MAX_STEPS = 50;

    public function __construct(private NodeExecutorRegistry $registry) {}

    /** Bắt đầu flow cho hội thoại. Trả run mới, hoặc run đang chạy nếu đã có. */
    public function start(AutomationFlow $flow, Conversation $conv, ?string $inboundBody = null): FlowRun
    {
        $existing = FlowRun::withoutGlobalScope(TenantScope::class)
            ->where('flow_id', $flow->id)
            ->where('conversation_id', $conv->id)
            ->whereIn('status', [FlowRun::STATUS_ACTIVE, FlowRun::STATUS_WAITING])
            ->first();
        if ($existing) {
            return $existing; // idempotent — đã có run đang chạy
        }

        try {
            $run = FlowRun::create([
                'tenant_id' => $conv->tenant_id,
                'flow_id' => $flow->id,
                'conversation_id' => $conv->id,
                'status' => FlowRun::STATUS_ACTIVE,
                'context' => [],
                'entered_at' => Carbon::now(),
            ]);
        } catch (QueryException) {
            // Race: run khác vừa tạo (unique) ⇒ lấy lại.
            return FlowRun::withoutGlobalScope(TenantScope::class)
                ->where('flow_id', $flow->id)->where('conversation_id', $conv->id)
                ->whereIn('status', [FlowRun::STATUS_ACTIVE, FlowRun::STATUS_WAITING])->firstOrFail();
        }

        $graph = new FlowGraph((array) $flow->graph);
        $trigger = $graph->triggerNode();
        if (! $trigger) {
            $run->update(['status' => FlowRun::STATUS_FAILED, 'error' => 'no_trigger_node']);

            return $run;
        }

        return $this->walk($run, $conv, $graph, $trigger->id, $inboundBody);
    }

    /** Tiến tiếp 1 run đang `waiting` khi có inbound mới. */
    public function resume(FlowRun $run, Conversation $conv, ?string $inboundBody = null): FlowRun
    {
        if ($run->status !== FlowRun::STATUS_WAITING || ! $run->current_node_id) {
            return $run;
        }
        $flow = AutomationFlow::withoutGlobalScope(TenantScope::class)->find($run->flow_id);
        if (! $flow) {
            $run->update(['status' => FlowRun::STATUS_FAILED, 'error' => 'flow_missing']);

            return $run;
        }

        return $this->walk($run, $conv, new FlowGraph((array) $flow->graph), $run->current_node_id, $inboundBody);
    }

    /** Đi từ `$startNodeId` qua các node cho tới khi chờ / kết thúc / lỗi. */
    private function walk(FlowRun $run, Conversation $conv, FlowGraph $graph, string $startNodeId, ?string $inboundBody): FlowRun
    {
        $run->update(['status' => FlowRun::STATUS_ACTIVE]);
        $nodeId = $startNodeId;

        for ($step = 0; $step < self::MAX_STEPS; $step++) {
            $node = $graph->node($nodeId);
            if (! $node) {
                $run->update(['status' => FlowRun::STATUS_ENDED, 'last_advanced_at' => Carbon::now()]);

                return $run;
            }
            if (! $this->registry->has($node->type)) {
                Log::warning('flow.unknown_node_type', ['type' => $node->type, 'flow_run' => $run->id]);
                $run->update(['status' => FlowRun::STATUS_FAILED, 'error' => 'unknown_node:'.$node->type]);

                return $run;
            }

            $ctx = new FlowContext($conv, $run, $inboundBody);
            $result = $this->registry->for($node->type)->execute($node, $ctx);
            $run = $run->fresh() ?? $run; // executor có thể đã update context

            if ($result->isWait()) {
                $run->update(['status' => FlowRun::STATUS_WAITING, 'current_node_id' => $nodeId, 'last_advanced_at' => Carbon::now()]);

                return $run;
            }
            if ($result->isEnd()) {
                $run->update(['status' => FlowRun::STATUS_COMPLETED, 'current_node_id' => $nodeId, 'last_advanced_at' => Carbon::now()]);

                return $run;
            }
            if ($result->isFail()) {
                $run->update(['status' => FlowRun::STATUS_FAILED, 'error' => $result->error, 'current_node_id' => $nodeId]);

                return $run;
            }

            // advance: chỉ "tiêu thụ" inbound cho node chờ đầu tiên (wait_reply),
            // các node sau trong cùng lượt vẫn thấy inbound để điều kiện hoạt động.
            $next = $graph->nextNodeId($nodeId, $result->handle);
            if ($next === null) {
                $run->update(['status' => FlowRun::STATUS_ENDED, 'last_advanced_at' => Carbon::now()]);

                return $run;
            }
            $nodeId = $next;
        }

        Log::warning('flow.max_steps_exceeded', ['flow_run' => $run->id]);
        $run->update(['status' => FlowRun::STATUS_FAILED, 'error' => 'max_steps_exceeded']);

        return $run;
    }
}
```

> Note on `wait_reply` + `resume`: when `resume()` is called, `current_node_id` is the wait node and `inboundBody` is set, so `WaitReplyNodeExecutor` returns `advance()` and the walk continues into the condition. On the initial `start()` the wait node has `inboundBody` from the triggering message too — to make the *first* wait actually pause, the wait node must not immediately advance on the triggering message. Fix: `start()` passes `inboundBody: null` into the walk (the trigger message already consumed), so the first `wait_reply` pauses; `resume()` passes the real `inboundBody`. Update `start()`'s final line to `return $this->walk($run, $conv, $graph, $trigger->id, null);` and keep the `$inboundBody` param only for future trigger-aware nodes. Adjust the test accordingly (it already expects the first wait to pause).

- [ ] **Step 4: Apply the `start()` fix from the note**

In `start()`, change the final return to:

```php
        return $this->walk($run, $conv, $graph, $trigger->id, null);
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=FlowEngineTest`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Messaging/Services/Flows/FlowEngine.php app/tests/Feature/Messaging/Flows/FlowEngineTest.php
git commit -m "feat(messaging): FlowEngine start/advance/resume with idempotent run guard"
```

---

## Task 9: `FlowMatcher` (find active flows for a trigger)

**Files:**
- Create: `app/app/Modules/Messaging/Services/Flows/FlowMatcher.php`

- [ ] **Step 1: Write `FlowMatcher`**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows;

use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Collection;

/**
 * Tìm các flow ACTIVE khớp 1 trigger cho 1 conversation. Chạy ngoài auth tenant ⇒
 * withoutGlobalScope + lọc tenant_id tường minh. KHÔNG hardcode tên provider —
 * provider lấy từ conversation, lọc theo flow.provider.
 */
class FlowMatcher
{
    /**
     * @param  list<string>  $triggerTypes  ưu tiên theo thứ tự truyền vào
     * @return Collection<int,AutomationFlow>
     */
    public function matching(Conversation $conv, array $triggerTypes, ?string $inboundBody = null): Collection
    {
        $flows = AutomationFlow::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $conv->tenant_id)
            ->where('provider', $conv->provider)
            ->where('status', AutomationFlow::STATUS_ACTIVE)
            ->where('enabled', true)
            ->whereIn('trigger_type', $triggerTypes)
            ->orderBy('id')
            ->get()
            // Ưu tiên theo thứ tự $triggerTypes — sort PHP-side để CHẠY ĐƯỢC trên cả
            // Postgres (prod) lẫn SQLite (test, :memory:). Trigger ngoài list ⇒ cuối.
            ->sortBy(fn (AutomationFlow $flow) => ($p = array_search($flow->trigger_type, $triggerTypes, true)) === false ? PHP_INT_MAX : $p)
            ->values();

        return $flows->filter(fn (AutomationFlow $flow) => $this->triggerConditionMet($flow, $conv, $inboundBody))->values();
    }

    private function triggerConditionMet(AutomationFlow $flow, Conversation $conv, ?string $inboundBody): bool
    {
        $cfg = (array) $flow->trigger_config;

        return match ($flow->trigger_type) {
            AutomationFlow::TRIGGER_INBOX_FIRST_MESSAGE => (int) $conv->message_count <= 1,
            AutomationFlow::TRIGGER_INBOX_ANY, AutomationFlow::TRIGGER_COMMENT_ANY => true,
            AutomationFlow::TRIGGER_INBOX_KEYWORD => $this->keywordHit($cfg, $inboundBody),
            // comment_on_post: lọc theo post_id lưu ở conversation.meta.fb_post_id.
            AutomationFlow::TRIGGER_COMMENT_ON_POST => $this->postMatches($cfg, $conv),
            default => false,
        };
    }

    private function keywordHit(array $cfg, ?string $inboundBody): bool
    {
        $haystack = mb_strtolower((string) $inboundBody);
        if ($haystack === '') {
            return false;
        }
        foreach ((array) ($cfg['keywords'] ?? []) as $kw) {
            $kw = mb_strtolower(trim((string) $kw));
            if ($kw !== '' && str_contains($haystack, $kw)) {
                return true;
            }
        }

        return false;
    }

    private function postMatches(array $cfg, Conversation $conv): bool
    {
        $postIds = array_map('strval', (array) ($cfg['post_ids'] ?? []));
        if ($postIds === []) {
            return false;
        }
        $convPost = (string) (($conv->meta ?? [])['fb_post_id'] ?? '');

        return $convPost !== '' && in_array($convPost, $postIds, true);
    }
}
```

> Note: Sort is PHP-side (not SQL `array_position`) because **tests run on SQLite `:memory:`** (`app/phpunit.xml`: `DB_CONNECTION=sqlite`) while prod is Postgres — the PHP sort is correct on both. Keep every `Flows` query DB-agnostic.

- [ ] **Step 2: Commit**

```bash
git add app/Modules/Messaging/Services/Flows/FlowMatcher.php
git commit -m "feat(messaging): FlowMatcher (active flows by trigger, provider-agnostic)"
```

---

## Task 10: Listeners + provider wiring + executor registration

**Files:**
- Create: `app/app/Modules/Messaging/Listeners/StartFlowOnInbound.php`
- Create: `app/app/Modules/Messaging/Listeners/StartFlowOnComment.php`
- Modify: `app/app/Modules/Messaging/MessagingServiceProvider.php`
- Test: `app/tests/Feature/Messaging/Flows/FlowListenersTest.php`

- [ ] **Step 1: Confirm event payloads** — Read `app/app/Modules/Messaging/Events/MessageReceived.php` and confirm it exposes `$conversationId` and a way to get the inbound body (it pairs with `$messageId`). `CommentReceived` exposes `public int $messageId, public int $conversationId` (confirmed). Both listeners load `Conversation` + the inbound `Message` body by id.

- [ ] **Step 2: Write `StartFlowOnInbound`**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Listeners;

use CMBcoreSeller\Modules\Messaging\Events\MessageReceived;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowEngine;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowMatcher;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * MessageReceived (DM) → nếu có run đang `waiting` ⇒ resume; ngược lại tìm flow
 * khớp (first_message → keyword → any) và start. Một conversation chỉ 1 run/flow.
 */
class StartFlowOnInbound implements ShouldQueue
{
    public string $queue = 'messaging';

    public function __construct(private FlowEngine $engine, private FlowMatcher $matcher) {}

    public function handle(MessageReceived $event): void
    {
        $conv = Conversation::withoutGlobalScope(TenantScope::class)->find($event->conversationId);
        if (! $conv || $conv->thread_type !== Conversation::THREAD_MESSAGE) {
            return;
        }
        $body = (string) (Message::withoutGlobalScope(TenantScope::class)->find($event->messageId)?->body ?? '');

        // 1) Resume run đang chờ (nếu có) — ưu tiên trước khi start flow mới.
        $waiting = FlowRun::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)->where('status', FlowRun::STATUS_WAITING)->first();
        if ($waiting) {
            $this->engine->resume($waiting, $conv, $body);

            return;
        }

        // 2) Start flow khớp trigger (theo thứ tự ưu tiên).
        $flows = $this->matcher->matching($conv, [
            AutomationFlow::TRIGGER_INBOX_FIRST_MESSAGE,
            AutomationFlow::TRIGGER_INBOX_KEYWORD,
            AutomationFlow::TRIGGER_INBOX_ANY,
        ], $body);
        if ($flow = $flows->first()) {
            $this->engine->start($flow, $conv, $body);
        }
    }
}
```

- [ ] **Step 3: Write `StartFlowOnComment`**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Listeners;

use CMBcoreSeller\Modules\Messaging\Events\CommentReceived;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowEngine;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowMatcher;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * CommentReceived → tìm flow comment khớp (comment_on_post → comment_any) và start.
 * Comment KHÔNG resume DM run; mỗi comment-conversation chạy độc lập.
 */
class StartFlowOnComment implements ShouldQueue
{
    public string $queue = 'messaging';

    public function __construct(private FlowEngine $engine, private FlowMatcher $matcher) {}

    public function handle(CommentReceived $event): void
    {
        $conv = Conversation::withoutGlobalScope(TenantScope::class)->find($event->conversationId);
        if (! $conv || $conv->thread_type !== Conversation::THREAD_COMMENT) {
            return;
        }
        $body = (string) (Message::withoutGlobalScope(TenantScope::class)->find($event->messageId)?->body ?? '');

        $flows = $this->matcher->matching($conv, [
            AutomationFlow::TRIGGER_COMMENT_ON_POST,
            AutomationFlow::TRIGGER_COMMENT_ANY,
        ], $body);
        if ($flow = $flows->first()) {
            $this->engine->start($flow, $conv, $body);
        }
    }
}
```

- [ ] **Step 4: Register the executor registry + listeners in `MessagingServiceProvider`**

In `register()`, add a singleton that wires the default executors (extensible — new node type = one more `register()` line here, never touching the engine):

```php
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\NodeExecutorRegistry;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\TriggerNodeExecutor;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\SendMessageNodeExecutor;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\SendCommentReplyNodeExecutor;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\ConditionNodeExecutor;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\WaitReplyNodeExecutor;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\EndNodeExecutor;
```

```php
        $this->app->singleton(NodeExecutorRegistry::class, function ($app) {
            $registry = new NodeExecutorRegistry($app);
            $registry->register('trigger', TriggerNodeExecutor::class);
            $registry->register('send_message', SendMessageNodeExecutor::class);
            $registry->register('send_comment_reply', SendCommentReplyNodeExecutor::class);
            $registry->register('condition', ConditionNodeExecutor::class);
            $registry->register('wait_reply', WaitReplyNodeExecutor::class);
            $registry->register('end', EndNodeExecutor::class);

            return $registry;
        });
```

In `boot()`, add next to the existing `Event::listen(...)` lines:

```php
use CMBcoreSeller\Modules\Messaging\Listeners\StartFlowOnInbound;
use CMBcoreSeller\Modules\Messaging\Listeners\StartFlowOnComment;
```

```php
        // Flow Builder (S1): kịch bản đa bước. Chạy SONG SONG auto-reply phẳng;
        // (ưu tiên flow vs rule sẽ tinh chỉnh ở slice sau — xem spec §11.2).
        Event::listen(MessageReceived::class, StartFlowOnInbound::class);
        Event::listen(CommentReceived::class, StartFlowOnComment::class);
```

- [ ] **Step 5: Write the feature test**

```php
<?php

namespace Tests\Feature\Messaging\Flows;

use CMBcoreSeller\Modules\Messaging\Events\MessageReceived;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FlowListenersTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_first_message_starts_matching_flow(): void
    {
        Queue::fake();

        $conv = Conversation::create([
            'tenant_id' => 1, 'channel_account_id' => 1, 'provider' => 'facebook_page',
            'thread_type' => Conversation::THREAD_MESSAGE, 'external_conversation_id' => 'c1',
            'buyer_external_id' => 'b1', 'status' => 'open', 'message_count' => 1,
        ]);
        $msg = Message::create([
            'tenant_id' => 1, 'conversation_id' => $conv->id, 'direction' => Message::DIRECTION_INBOUND,
            'kind' => Message::KIND_TEXT, 'body' => 'xin chào', 'delivery_status' => Message::STATUS_SENT,
        ]);
        AutomationFlow::create([
            'tenant_id' => 1, 'name' => 'Greeting', 'provider' => 'facebook_page',
            'status' => AutomationFlow::STATUS_ACTIVE, 'trigger_type' => AutomationFlow::TRIGGER_INBOX_FIRST_MESSAGE,
            'enabled' => true,
            'graph' => [
                'nodes' => [
                    ['id' => 't', 'type' => 'trigger', 'data' => []],
                    ['id' => 'g', 'type' => 'send_message', 'data' => ['text' => 'Chào bạn!']],
                    ['id' => 'e', 'type' => 'end', 'data' => []],
                ],
                'edges' => [
                    ['source' => 't', 'target' => 'g', 'sourceHandle' => null],
                    ['source' => 'g', 'target' => 'e', 'sourceHandle' => null],
                ],
            ],
        ]);

        // Listener runs synchronously in tests (Queue::fake only fakes jobs, not listeners
        // dispatched via Event — ShouldQueue listeners are queued; use Bus/Queue fake +
        // call listener directly for determinism):
        (new \CMBcoreSeller\Modules\Messaging\Listeners\StartFlowOnInbound(
            app(\CMBcoreSeller\Modules\Messaging\Services\Flows\FlowEngine::class),
            app(\CMBcoreSeller\Modules\Messaging\Services\Flows\FlowMatcher::class),
        ))->handle(new MessageReceived($msg->id, $conv->id));

        $this->assertSame(1, FlowRun::where('conversation_id', $conv->id)->where('status', FlowRun::STATUS_COMPLETED)->count());
        $this->assertSame(1, Message::where('conversation_id', $conv->id)->where('body', 'Chào bạn!')->count());
    }
}
```

> Note: confirm `MessageReceived`'s constructor argument order by reading the event file in Step 1; adjust `new MessageReceived(...)` to match (the codebase uses `{message_id, conversation_id, requires_human}` per SPEC-0024 §5.14 — pass the first two).

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=FlowListenersTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Modules/Messaging/Listeners/StartFlowOnInbound.php app/Modules/Messaging/Listeners/StartFlowOnComment.php app/Modules/Messaging/MessagingServiceProvider.php app/tests/Feature/Messaging/Flows/FlowListenersTest.php
git commit -m "feat(messaging): wire flow engine to inbound/comment events + register node executors"
```

---

## Task 11: Quality gate

- [ ] **Step 1: Format**

Run: `vendor/bin/pint app/Modules/Messaging/Services/Flows app/Modules/Messaging/Models/AutomationFlow.php app/Modules/Messaging/Models/FlowRun.php app/Modules/Messaging/Listeners/StartFlowOnInbound.php app/Modules/Messaging/Listeners/StartFlowOnComment.php`
Expected: `result: fixed` or `passed`.

- [ ] **Step 2: Static analysis**

Run: `vendor/bin/phpstan analyse app/Modules/Messaging/Services/Flows app/Modules/Messaging/Models/AutomationFlow.php app/Modules/Messaging/Models/FlowRun.php`
Expected: `[OK] No errors`. Fix any reported issue inline.

- [ ] **Step 3: Full messaging test run**

Run: `php artisan test --filter=Flow`
Expected: all flow tests PASS.
Run: `php artisan test tests/Feature/Messaging tests/Unit/Messaging`
Expected: no NEW failures vs the pre-existing baseline (7 GHN/fulfillment fails on main are unrelated).

- [ ] **Step 4: Final commit**

```bash
git add -A app/Modules/Messaging
git commit -m "chore(messaging): flow builder S1 quality gate (pint + phpstan + tests green)"
```

---

## Self-Review

**Spec coverage (S1 scope only):**
- Data model `automation_flows` + `flow_runs` → Tasks 1–4. ✓
- `FlowEngine` immediate-vs-wait execution + resume → Task 8. ✓
- Node executor extensibility (registry, no hardcoded switch) → Tasks 6–7, 10. ✓
- Reuse `OutboundMessageService` / `CommentReplyService` (audit + 24h guard) → Task 7. ✓
- Entry on inbound + comment, provider-agnostic matcher → Tasks 9–10. ✓
- Idempotency (one run per conversation, no resend) → Tasks 2, 7, 8. ✓
- **Deferred to later slices (not S1):** postback/`sendInteractive` (S2), canvas UI (S3), interactive templates + post picker + comment_on_post UI (S4), AI node + handoff + analytics (S5). Flagged in spec §13.

**Placeholder scan:** No "TBD/handle edge cases/implement later". The two `> Note:` blocks (DB dialect in Task 9, event-arg order in Task 10) are explicit verification instructions with concrete fallback code, not placeholders.

**Type consistency:** `NodeResult` factories (`advance/wait/end/fail`) + predicates (`isAdvance/isWait/isEnd/isFail`) used consistently in executors + engine. `FlowGraph::nextNodeId($from, $handle)`, `node($id)`, `triggerNode()` match call sites. `FlowRun` status constants used uniformly. `FlowContext(conversation, run, inboundBody)` constructor matches all `new FlowContext(...)` sites.

**Known follow-ups for S2+ (not bugs):** flow-vs-flat-rule priority (spec §11.2); per-node send idempotency could move from `context._sent` to `auto_reply_runs` keys when comment public replies need cross-job dedupe.
