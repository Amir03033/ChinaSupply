<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allow-list sanitiser for CMS HTML written in Filament's rich editor.
 * Admins are trusted, but an allow-list keeps a compromised admin account or
 * pasted content from injecting scripts into public pages.
 */
class SafeHtml
{
    private const ALLOWED_TAGS = ['p', 'br', 'h2', 'h3', 'h4', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li', 'a', 'blockquote', 'code', 'pre', 'hr', 'table', 'thead', 'tbody', 'tr', 'th', 'td'];

    public static function clean(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        $doc = new DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $root = $doc->getElementById('root');
        if (! $root) {
            return '';
        }

        self::walk($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    // Drop dangerous containers entirely, unwrap the rest.
                    if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'svg', 'math'], true)) {
                        $node->removeChild($child);

                        continue;
                    }
                    self::walk($child);
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);

                    continue;
                }

                foreach (iterator_to_array($child->attributes) as $attr) {
                    $keep = $tag === 'a' && $attr->name === 'href' && preg_match('~^(https?://|mailto:|/|#)~i', trim($attr->value));
                    if (! $keep) {
                        $child->removeAttribute($attr->name);
                    }
                }

                if ($tag === 'a' && preg_match('~^https?://~i', (string) $child->getAttribute('href'))) {
                    $child->setAttribute('rel', 'noopener nofollow');
                }

                self::walk($child);
            } elseif ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
            }
        }
    }
}
