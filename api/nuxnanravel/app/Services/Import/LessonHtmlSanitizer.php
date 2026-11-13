<?php

namespace App\Services\Import;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Allowlist-based HTML sanitizer for lesson content imported from .docx files.
 *
 * The HTML comes from the browser (mammoth.js output) and is UNTRUSTED.
 * - Dangerous tags are removed together with their content.
 * - Unknown tags are unwrapped (children kept, tag dropped).
 * - Only a handful of attributes survive; everything else (on*, style, class, id, ...) is removed.
 */
final class LessonHtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup',
        'ul', 'ol', 'li',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
        'blockquote', 'a', 'img', 'span', 'hr', 'pre', 'code', 'caption',
    ];

    private const DROP_WITH_CONTENT_TAGS = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select',
        'link', 'meta', 'base', 'svg', 'math', 'noscript', 'template',
    ];

    /** @var array<string, list<string>> */
    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href'],
        'img' => ['src', 'alt'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
        'ol' => ['start'],
    ];

    private const ROOT_ID = '__root';

    public function sanitize(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);

        try {
            $doc = new DOMDocument('1.0', 'UTF-8');
            $doc->loadHTML(
                '<?xml encoding="UTF-8"><div id="'.self::ROOT_ID.'">'.$html.'</div>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
            );
            libxml_clear_errors();

            $root = (new DOMXPath($doc))->query('//div[@id="'.self::ROOT_ID.'"]')->item(0);
            if (! $root instanceof DOMElement) {
                return '';
            }

            $this->cleanChildren($root);

            $out = '';
            foreach ($root->childNodes as $child) {
                $out .= $doc->saveHTML($child);
            }

            return trim($out);
        } finally {
            libxml_use_internal_errors($previous);
        }
    }

    private function cleanChildren(DOMNode $parent): void
    {
        // Copy first: the live NodeList changes while we remove/unwrap.
        $children = iterator_to_array($parent->childNodes);

        foreach ($children as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                // Comments, CDATA, processing instructions, ...
                $parent->removeChild($child);

                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT_TAGS, true)) {
                $parent->removeChild($child);

                continue;
            }

            // Sanitize descendants first so whatever we unwrap is already clean.
            $this->cleanChildren($child);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                while ($child->firstChild) {
                    $parent->insertBefore($child->firstChild, $child);
                }
                $parent->removeChild($child);

                continue;
            }

            $this->cleanAttributes($child, $tag);
        }
    }

    private function cleanAttributes(DOMElement $el, string $tag): void
    {
        $allowed = self::ALLOWED_ATTRIBUTES[$tag] ?? [];

        $names = [];
        foreach ($el->attributes as $attr) {
            $names[] = $attr->nodeName;
        }

        foreach ($names as $name) {
            $lower = strtolower($name);
            if (! in_array($lower, $allowed, true)) {
                $el->removeAttribute($name);

                continue;
            }

            $value = trim($el->getAttribute($name));

            $keep = match ($lower) {
                'href' => $this->isSafeHref($value),
                'colspan', 'rowspan' => preg_match('/^\d{1,3}$/', $value) === 1 && (int) $value >= 1 && (int) $value <= 100,
                'start' => preg_match('/^\d{1,6}$/', $value) === 1,
                default => true,
            };

            if (! $keep) {
                $el->removeAttribute($name);
            }
        }

        if ($tag === 'a' && $el->hasAttribute('href') && preg_match('#^https?://#i', trim($el->getAttribute('href')))) {
            $el->setAttribute('target', '_blank');
            $el->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private function isSafeHref(string $href): bool
    {
        return preg_match('#^(https?://|mailto:|\#)#i', $href) === 1;
    }
}
