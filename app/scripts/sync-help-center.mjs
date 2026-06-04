// Đồng bộ nội dung Trung tâm trợ giúp: copy support_doc/*.md (nguồn canon ở repo root)
// vào app/resources/help-center/ để Vite đóng gói vào bundle FE.
//
// - Nguồn: <repo>/support_doc/*.md (trừ README.md — đó là mục lục, không phải bài).
// - Đích:  app/resources/help-center/*.md (đã commit; script chỉ làm tươi khi có nguồn).
// - Nếu thư mục nguồn KHÔNG tồn tại (vd build trên môi trường không có support_doc),
//   script bỏ qua và GIỮ NGUYÊN bản đã commit (không xoá) để build vẫn chạy.
//
// Chạy: `npm run help:sync` (và tự chạy trước `npm run build`).
import { existsSync, mkdirSync, readdirSync, copyFileSync, rmSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const srcDir = resolve(here, '../../support_doc');
const destDir = resolve(here, '../resources/help-center');

if (!existsSync(srcDir)) {
    console.log(`[help-center] Bỏ qua: không thấy nguồn ${srcDir} — giữ nội dung đã commit.`);
    process.exit(0);
}

mkdirSync(destDir, { recursive: true });

const isArticle = (name) => name.endsWith('.md') && name.toLowerCase() !== 'readme.md';

// Xoá bài cũ ở đích để tránh sót bài đã đổi tên/xoá ở nguồn.
for (const name of readdirSync(destDir)) {
    if (isArticle(name)) rmSync(join(destDir, name));
}

let n = 0;
for (const name of readdirSync(srcDir)) {
    if (!isArticle(name)) continue;
    copyFileSync(join(srcDir, name), join(destDir, name));
    n++;
}

console.log(`[help-center] Đã đồng bộ ${n} bài từ support_doc/ -> resources/help-center/`);
