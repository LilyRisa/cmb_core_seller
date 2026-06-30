<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;

/**
 * "Rẽ theo bài viết": rẽ nhánh theo bài viết NGUỒN của hội thoại
 * (`conversation.meta.fb_post_id` — gắn bởi CommentDmLinker khi DM bắt nguồn từ
 * bình luận, hoặc trên hội thoại bình luận). Mỗi bài đã chọn = 1 handle (= post id);
 * không khớp bài nào ⇒ handle `default`.
 *
 * Đồng bộ như {@see ConditionNodeExecutor} (KHÔNG chờ postback): tính nhánh ngay rồi
 * `advance($handle)`. `FlowGraph::nextNodeId` so `edge.sourceHandle === $handle`.
 * Dùng khi flow áp nhiều trang/nhiều bài để mỗi bài chạy logic riêng (quyết định 2.3).
 */
class PostRouterNodeExecutor implements NodeExecutor
{
    public function type(): string
    {
        return 'post_router';
    }

    public function execute(FlowNode $node, FlowContext $ctx): NodeResult
    {
        $convPost = (string) (($ctx->conversation->meta ?? [])['fb_post_id'] ?? '');

        if ($convPost !== '') {
            foreach ((array) ($node->data['posts'] ?? []) as $p) {
                $id = is_array($p) ? (string) ($p['id'] ?? '') : (string) $p;
                if ($id !== '' && $id === $convPost) {
                    return NodeResult::advance($id);
                }
            }
        }

        return NodeResult::advance('default');
    }
}
