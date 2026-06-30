<?php

namespace CMBcoreSeller\Integrations\Messaging\Contracts;

use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;

/**
 * Năng lực RIÊNG: liệt kê bài đăng của trang để người dùng chọn (post picker cho
 * trigger `comment_on_post`). Tách KHỎI {@see MessagingConnector} — chỉ Facebook
 * Page hỗ trợ; các sàn khác KHÔNG bị buộc implement (Interface Segregation).
 *
 * GOLDEN RULE: core kiểm `instanceof ListsPostsConnector` (tên NĂNG LỰC, không phải
 * tên sàn) trước khi gọi. Sàn nào có "bài đăng + bình luận" sau này chỉ cần
 * implement interface này — không sửa core.
 */
interface ListsPostsConnector
{
    /**
     * Liệt kê bài đăng đã xuất bản của trang (mới nhất trước) để chọn áp dụng kịch bản.
     *
     * @param  array{pageSize?:int, cursor?:string}  $query
     * @return array{items: list<array{id:string, message:?string, permalink_url:?string, image_url:?string, created_time:?string, likes:int, comments:int, shares:int}>, nextCursor:?string, hasMore:bool}
     */
    public function listPosts(MessagingAuthContext $auth, array $query = []): array;
}
