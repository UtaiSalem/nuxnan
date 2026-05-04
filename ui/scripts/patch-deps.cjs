#!/usr/bin/env node
// Auto-patches npm packages that have broken/incomplete builds caused by debug-logging lines
// being removed from source but leaving dangling if-conditions and incomplete arrow functions.
// Run automatically via "postinstall" in package.json.
const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');
const nm = path.join(root, 'node_modules');
let patched = 0;
let checked = 0;

const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

// ─── Pattern fixers ─────────────────────────────────────────────────────────

function fixContent(content, relPath) {
  let fixed = content;

  // Pattern A: `if (EXPR)\r?\n\r?\n  LEXICAL` → add {} to if body
  // Covers: yaml, webpack-virtual-modules, @vercel/nft, tailwindcss patterns
  fixed = fixed.replace(
    /(\bif\s*\([^)]+\))\r?\n(\r?\n\s+)(const|let|class)\s/g,
    (_, ifPart, nl, kwd) => `${ifPart} {}${nl}${kwd} `
  );

  // Pattern B: `EXPR &&\r?\n  LEXICAL` — incomplete short-circuit debug expr
  // Covers: tailwindcss _sharedState.env.DEBUG &&  \n let existingContext
  fixed = fixed.replace(
    /([^\n]+\s&&)\s*\r?\n(\s*)(const|let|var|class)\s/g,
    (_, expr, indent, kwd) => `${expr.trimEnd()} (()=>{})();\n${indent}${kwd} `
  );

  // Pattern C: `=> ,` inside object literal — missing arrow function body
  // Covers: vite module-runner.js hmrLogger pattern (spaces)
  fixed = fixed.replace(/=>\s*,\n(\s+\w)/g, '=> {},\n$1');

  // Pattern D: `=> \n}` — arrow function with no body before closing
  // Covers: vite module-runner.js error: (error) =>  \n};
  fixed = fixed.replace(/=>\s*\r?\n([\t ]*)\}/g, '=> {}\n$1}');

  return fixed;
}

// ─── Specific known fixes not caught by general patterns ─────────────────────

function fixKnownIssues(content, relPath) {
  let fixed = content;

  // @speed-highlight/core@1.2.15 — la arrow function body missing, ua undefined
  if (relPath.includes('@speed-highlight/core/dist/terminal.js')) {
    fixed = fixed.replace(
      ',la=async(n,t)=>export{ke as highlightText,la as printHighlight,ua as setTheme};',
      ',la=async(n,t)=>{},ua=async()=>{};export{ke as highlightText,la as printHighlight,ua as setTheme};'
    );
  }

  // tailwindcss — setupContextUtils.js: _sharedState.env.DEBUG && \n  let existingContext
  if (relPath.includes('tailwindcss/lib/lib/setupContextUtils.js')) {
    fixed = fixed.replace(
      /(_sharedState\.env\.DEBUG)\s*&&\s*\r?\n(\s*let\s)/g,
      (_, expr, indent) => `${expr}; // [debug]\n${indent}let `
    );
  }

  return fixed;
}

// ─── File scanner ────────────────────────────────────────────────────────────

function shouldScan(filePath) {
  const ext = path.extname(filePath);
  if (ext !== '.js' && ext !== '.mjs') return false;
  // Skip test files and source maps
  const rel = filePath.replace(nm + path.sep, '');
  if (rel.includes('.min.') || rel.endsWith('.map')) return false;
  // Focus on the packages we know are problematic and their deps
  return true;
}

function scanDir(dir, depth = 0) {
  if (depth > 6) return;
  let entries;
  try { entries = fs.readdirSync(dir); } catch { return; }

  for (const name of entries) {
    if (name.startsWith('.')) continue;
    const fullPath = path.join(dir, name);
    let stat;
    try { stat = fs.statSync(fullPath); } catch { continue; }

    if (stat.isDirectory()) {
      // Recurse, but don't go into nested node_modules of already-nested packages
      if (name === 'node_modules' && depth > 0) {
        scanDir(fullPath, depth + 1);
      } else if (name !== 'node_modules') {
        scanDir(fullPath, depth + 1);
      }
    } else if (stat.isFile() && stat.size <= MAX_FILE_SIZE && shouldScan(fullPath)) {
      processFile(fullPath);
    }
  }
}

function processFile(fullPath) {
  checked++;
  let content;
  try { content = fs.readFileSync(fullPath, 'utf8'); } catch { return; }

  const relPath = fullPath.replace(nm + path.sep, '').replace(/\\/g, '/');

  // Avoid patching Tailwind internals; false positives here can break Nuxt builds.
  if (relPath.startsWith('tailwindcss/')) return;

  let fixed = fixKnownIssues(content, relPath);
  fixed = fixContent(fixed, relPath);

  if (fixed !== content) {
    fs.writeFileSync(fullPath, fixed, 'utf8');
    console.log(`[patch] Fixed: ${relPath}`);
    patched++;
  }
}

// ─── Main ────────────────────────────────────────────────────────────────────

console.log('[patch] Scanning node_modules for broken debug-logging patterns...');
if (process.env.PATCH_DEPS !== '1') {
  console.log('[patch] Skipped. Set PATCH_DEPS=1 to enable dependency patching.');
  process.exit(0);
}
scanDir(nm);
console.log(`[patch] Done: ${patched} files patched out of ${checked} checked.\n`);
