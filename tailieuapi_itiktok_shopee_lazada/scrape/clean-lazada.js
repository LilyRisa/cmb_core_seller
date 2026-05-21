/*
 * clean-lazada.js
 *
 * Cleans up Markdown table formatting in all lazada/*.md files.
 * - Collapses blank lines inside table cells (between | delimiters)
 * - Ensures each table row is on a single line
 * - Skips content inside fenced code blocks (``` ... ```)
 * - Operates idempotently (safe to run multiple times)
 *
 * Usage:
 *   node clean-lazada.js [--dir <path>]
 *   Default dir: ../lazada (relative to script location)
 */

const fs = require('fs');
const path = require('path');

const args = process.argv.slice(2);
function argVal(flag) {
  const i = args.indexOf(flag);
  return i >= 0 ? args[i + 1] : undefined;
}

const outDir = path.resolve(__dirname, argVal('--dir') || '../lazada');

if (!fs.existsSync(outDir)) {
  console.error('Directory not found:', outDir);
  process.exit(1);
}

const files = fs.readdirSync(outDir).filter(f => f.endsWith('.md') && !f.startsWith('_'));
console.log(`Cleaning ${files.length} markdown files in ${outDir}`);

let totalCleaned = 0;

for (const file of files) {
  const filePath = path.join(outDir, file);
  const original = fs.readFileSync(filePath, 'utf8');

  const cleaned = cleanMarkdown(original);

  if (cleaned !== original) {
    fs.writeFileSync(filePath, cleaned, 'utf8');
    totalCleaned++;
  }
}

console.log(`Done. ${totalCleaned} files modified.`);

function cleanMarkdown(text) {
  // Split into segments: code fences vs normal text
  // We process normal text segments but leave code blocks untouched
  const segments = splitByCodeFences(text);

  const processedSegments = segments.map(seg => {
    if (seg.isCode) return seg.text;
    return cleanTableRows(seg.text);
  });

  return processedSegments.join('');
}

function splitByCodeFences(text) {
  const segments = [];
  // Match opening ``` (with optional language tag) through closing ```
  const fenceRe = /^(`{3,})[^\n]*\n[\s\S]*?\n\1\s*$/gm;

  let lastIndex = 0;
  let match;

  // We use a manual approach to find fence blocks
  const lines = text.split('\n');
  let result = '';
  let inCode = false;
  let fenceMarker = '';
  let normalStart = 0;
  let segList = [];
  let currentNormal = '';
  let currentCode = '';

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    if (!inCode) {
      const fenceMatch = line.match(/^(`{3,}|~{3,})/);
      if (fenceMatch) {
        // Save accumulated normal text
        if (currentNormal) {
          segList.push({ isCode: false, text: currentNormal });
          currentNormal = '';
        }
        inCode = true;
        fenceMarker = fenceMatch[1];
        currentCode = line + '\n';
      } else {
        currentNormal += line + '\n';
      }
    } else {
      currentCode += line + '\n';
      // Check for closing fence: same or more backticks/tildes
      const closingMatch = line.match(/^(`{3,}|~{3,})\s*$/);
      if (closingMatch && closingMatch[1].charAt(0) === fenceMarker.charAt(0) && closingMatch[1].length >= fenceMarker.length) {
        inCode = false;
        segList.push({ isCode: true, text: currentCode });
        currentCode = '';
        fenceMarker = '';
      }
    }
  }

  // Flush remaining
  if (currentNormal) segList.push({ isCode: false, text: currentNormal });
  if (currentCode) segList.push({ isCode: true, text: currentCode }); // unclosed fence — treat as code

  return segList;
}

function cleanTableRows(text) {
  // Strategy: find table regions (lines that start with |) and clean them up
  // A "table region" is a contiguous block containing lines starting with |
  // interspersed with blank lines (which are intra-cell artifacts)

  const lines = text.split('\n');
  const result = [];
  let i = 0;

  while (i < lines.length) {
    const line = lines[i];

    // Detect start of a table region: line starts with |
    if (line.trimStart().startsWith('|')) {
      // Collect the table region: lines starting with | or blank lines within the table
      const tableLines = [];
      let j = i;

      while (j < lines.length) {
        const l = lines[j];
        const trimmed = l.trim();

        if (trimmed.startsWith('|')) {
          tableLines.push({ idx: j, content: l, isBlank: false });
          j++;
        } else if (trimmed === '') {
          // Blank line — could be inside a cell or end of table
          // Look ahead: if next non-blank line starts with |, this blank is intra-cell
          let k = j + 1;
          while (k < lines.length && lines[k].trim() === '') k++;
          if (k < lines.length && lines[k].trim().startsWith('|')) {
            tableLines.push({ idx: j, content: l, isBlank: true });
            j++;
          } else {
            // Blank line is end of table
            break;
          }
        } else {
          // Non-blank, non-table line: end of table
          break;
        }
      }

      // Now merge multi-line table rows
      // A "row" is a line starting with | potentially followed by blank lines before the next |
      // We want to join intra-row continuation text
      const mergedRows = mergeTableRows(tableLines);
      result.push(...mergedRows);
      i = j;
    } else {
      result.push(line);
      i++;
    }
  }

  return result.join('\n');
}

function mergeTableRows(tableLines) {
  // Each element is { content, isBlank }
  // Goal: collapse blank lines between table rows (they are just spacing artifacts)
  // The separator row (---|---|---) should stay as-is
  // Table rows that contain cell content split across lines should be merged

  const rows = [];
  let currentRow = null;

  for (const entry of tableLines) {
    if (entry.isBlank) {
      // Skip intra-table blank lines entirely
      continue;
    }

    const line = entry.content.trimEnd();

    if (currentRow === null) {
      currentRow = line;
    } else {
      // Check if previous and current are both table rows starting with |
      // If the current line starts with |, it's a new row
      if (line.trimStart().startsWith('|')) {
        rows.push(currentRow);
        currentRow = line;
      } else {
        // continuation content within a cell — append with space
        currentRow = currentRow.trimEnd() + ' ' + line.trim();
      }
    }
  }

  if (currentRow !== null) {
    rows.push(currentRow);
  }

  return rows;
}
