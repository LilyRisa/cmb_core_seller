/*
 * Post-process TikTok markdown files:
 * 1. Remove base64 image references (data: URIs)
 * 2. Fix "DocumentsNew" titles
 * 3. Clean up extra whitespace
 */

const fs = require('fs');
const path = require('path');

const TIKTOK_DIR = path.resolve(__dirname, '../tiktok');
const MANIFEST = path.join(TIKTOK_DIR, '_manifest.json');

const files = fs.readdirSync(TIKTOK_DIR).filter(f =>
  f.endsWith('.md') && f !== 'INDEX.md' && f !== 'README.md'
);

console.log(`Processing ${files.length} files...`);

let fixed = 0;
let titleFixed = 0;
let b64Removed = 0;

for (const f of files) {
  const filePath = path.join(TIKTOK_DIR, f);
  let content = fs.readFileSync(filePath, 'utf8');
  const original = content;

  // 1. Remove base64 images: ![...](data:image/...;base64,...)
  const before64 = content.length;
  content = content.replace(/!\[[^\]]*\]\(data:[^)]+\)/g, '');
  if (content.length < before64) b64Removed++;

  // 2. Remove "Is this content helpful?" + Helpful/Not Helpful links
  content = content.replace(/Is this content helpful\?[\s\S]*?Not Helpful\n/g, '');
  content = content.replace(/\*{0,2}Is this content helpful\??\*{0,2}\s*/g, '');

  // 3. Fix "DocumentsNew" in title
  const titleMatch = content.match(/^# (.+)$/m);
  if (titleMatch) {
    const title = titleMatch[1].trim();
    if (/^DocumentsNew$/i.test(title) || /^Documents$/i.test(title)) {
      // Try to derive from filename
      const slug = f.replace('docv2_page_', '').replace('docv2_faqs_', '').replace('.md', '');
      const betterTitle = slug.replace(/-/g, ' ').replace(/_/g, ' ')
        .replace(/\b\w/g, c => c.toUpperCase());
      content = content.replace(/^# .+$/m, `# ${betterTitle}`);
      titleFixed++;
    }
  }

  // 4. Remove excessive blank lines
  content = content.replace(/\n{4,}/g, '\n\n\n');

  // 5. Remove the feedback widget links (list items that are just navigation)
  content = content.replace(/^- \[(Request|Header|Query|Body|Example|Response|Parameters|Error Code)\]\(#[^)]+\)\n/gm, '');

  // 6. Clean up stray "Helpful" / "Not Helpful" text
  content = content.replace(/^\s*Helpful!\s*$/gm, '');
  content = content.replace(/^\s*Not Helpful\s*$/gm, '');

  if (content !== original) {
    fs.writeFileSync(filePath, content);
    fixed++;
  }
}

console.log(`Fixed: ${fixed} files`);
console.log(`Removed base64 images from: ${b64Removed} files`);
console.log(`Fixed titles in: ${titleFixed} files`);
console.log('Cleanup done!');
