<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeChunk;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeDocument;
use CMBcoreSeller\Modules\Messaging\Services\KnowledgeRetriever;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC 0035 — tài liệu AI training (knowledge) theo từng page.
 * Doc gán page A chỉ retrieve cho page A; doc applies_all_pages retrieve mọi page.
 */
class KnowledgeRetrieverPageScopeTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $pageA;

    private ChannelAccount $pageB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'KbMultiPage']);
        $this->pageA = $this->account('kb_a');
        $this->pageB = $this->account('kb_b');
    }

    private function account(string $ext): ChannelAccount
    {
        return ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => $ext, 'shop_name' => $ext, 'status' => 'active', 'messaging_enabled' => true,
        ]);
    }

    private function doc(string $title, string $text, bool $allPages, ?ChannelAccount $page = null): AiKnowledgeDocument
    {
        $doc = AiKnowledgeDocument::create([
            'tenant_id' => $this->tenant->getKey(), 'title' => $title, 'source' => 'inline',
            'inline_text' => $text, 'chunk_count' => 1, 'status' => AiKnowledgeDocument::STATUS_READY,
            'applies_all_pages' => $allPages,
        ]);
        AiKnowledgeChunk::create([
            'tenant_id' => $this->tenant->getKey(), 'document_id' => $doc->id,
            'chunk_index' => 0, 'chunk_text' => $text,
        ]);
        if ($page !== null) {
            $doc->pages()->attach($page->getKey(), ['tenant_id' => $this->tenant->getKey()]);
        }

        return $doc;
    }

    private function retriever(): KnowledgeRetriever
    {
        return app(KnowledgeRetriever::class);
    }

    public function test_page_specific_doc_only_retrieved_for_that_page(): void
    {
        $this->doc('Bảng giá page A', 'giá sản phẩm page a là 100k', allPages: false, page: $this->pageA);

        $forA = $this->retriever()->retrieve((int) $this->tenant->getKey(), 'giá', 4, (int) $this->pageA->getKey());
        $forB = $this->retriever()->retrieve((int) $this->tenant->getKey(), 'giá', 4, (int) $this->pageB->getKey());

        $this->assertCount(1, $forA->chunks);
        $this->assertCount(0, $forB->chunks, 'doc gán page A KHÔNG dùng cho page B');
    }

    public function test_all_pages_doc_retrieved_for_every_page(): void
    {
        $this->doc('Chính sách chung', 'chính sách đổi trả áp dụng toàn shop', allPages: true);

        $forA = $this->retriever()->retrieve((int) $this->tenant->getKey(), 'chính sách', 4, (int) $this->pageA->getKey());
        $forB = $this->retriever()->retrieve((int) $this->tenant->getKey(), 'chính sách', 4, (int) $this->pageB->getKey());

        $this->assertCount(1, $forA->chunks);
        $this->assertCount(1, $forB->chunks);
    }

    public function test_null_page_retrieves_all_docs(): void
    {
        $this->doc('Riêng A', 'thông tin abc riêng page a', allPages: false, page: $this->pageA);

        // Không truyền page (vd trợ lý help) ⇒ lấy tất cả (backward compat).
        $all = $this->retriever()->retrieve((int) $this->tenant->getKey(), 'abc', 4);

        $this->assertCount(1, $all->chunks);
    }

    public function test_facebook_doc_not_retrieved_for_zalo_provider(): void
    {
        // Tài liệu "áp mọi trang" của Facebook KHÔNG được dùng cho hội thoại Zalo OA.
        $doc = $this->doc('Chính sách FB', 'chính sách giao hàng toàn shop', allPages: true);
        $doc->update(['provider' => 'facebook_page']);

        $forFb = $this->retriever()->retrieve((int) $this->tenant->getKey(), 'chính sách', 4, (int) $this->pageA->getKey(), 'facebook_page');
        $forZalo = $this->retriever()->retrieve((int) $this->tenant->getKey(), 'chính sách', 4, (int) $this->pageA->getKey(), 'zalo_oa');

        $this->assertCount(1, $forFb->chunks, 'doc Facebook dùng cho hội thoại Facebook');
        $this->assertCount(0, $forZalo->chunks, 'doc Facebook KHÔNG rò sang hội thoại Zalo OA');
    }
}
