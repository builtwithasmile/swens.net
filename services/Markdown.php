<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Escape-first minimal Markdown renderer.
 * Escape the entire body first, then apply a closed set of transforms.
 * XSS-safe by construction: no raw HTML passthrough.
 * Supported: headings (##/###), bold, italic, inline code, fenced code,
 * blockquote, unordered/ordered lists, paragraphs, links (http/https only),
 * images (same-origin /media/ only).
 */
class Markdown
{
    public static function render(string $md): string
    {
        // 1. Normalise line endings
        $md = str_replace(["\r\n", "\r"], "\n", $md);

        // 2. Extract fenced code blocks BEFORE escaping (preserve verbatim content)
        $blocks = [];
        $md = preg_replace_callback(
            '/^```(\w*)\n(.*?)^```\s*$/ms',
            function ($m) use (&$blocks) {
                $ph = "\x00CODE" . count($blocks) . "\x00";
                $lang = e($m[1]);
                $code = htmlspecialchars($m[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $blocks[$ph] = $lang !== ''
                    ? "<pre><code class=\"language-{$lang}\">{$code}</code></pre>"
                    : "<pre><code>{$code}</code></pre>";
                return $ph;
            },
            $md
        );

        // 3. Escape remaining content
        $md = htmlspecialchars($md, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // 4. Restore code blocks
        foreach ($blocks as $ph => $html) {
            $md = str_replace($ph, $html, $md);
        }

        // 5. Process line-by-line block elements
        $lines = explode("\n", $md);
        $out = '';
        $i = 0;
        $total = count($lines);

        while ($i < $total) {
            $line = $lines[$i];

            // Fenced code block placeholder (already in $md, pass through)
            if (str_contains($line, "\x00CODE")) {
                $out .= $line . "\n";
                $i++;
                continue;
            }

            // Heading ### or ##
            if (preg_match('/^### (.+)$/', $line, $m)) {
                $out .= '<h3>' . self::inline($m[1]) . "</h3>\n";
                $i++;
                continue;
            }
            if (preg_match('/^## (.+)$/', $line, $m)) {
                $out .= '<h2>' . self::inline($m[1]) . "</h2>\n";
                $i++;
                continue;
            }

            // Blockquote
            if (preg_match('/^&gt; (.+)$/', $line, $m)) {
                $out .= '<blockquote><p>' . self::inline($m[1]) . "</p></blockquote>\n";
                $i++;
                continue;
            }

            // Unordered list
            if (preg_match('/^[*\-] (.+)$/', $line, $m)) {
                $out .= "<ul>\n";
                while ($i < $total && preg_match('/^[*\-] (.+)$/', $lines[$i], $m)) {
                    $out .= '<li>' . self::inline($m[1]) . "</li>\n";
                    $i++;
                }
                $out .= "</ul>\n";
                continue;
            }

            // Ordered list
            if (preg_match('/^\d+\. (.+)$/', $line, $m)) {
                $out .= "<ol>\n";
                while ($i < $total && preg_match('/^\d+\. (.+)$/', $lines[$i], $m)) {
                    $out .= '<li>' . self::inline($m[1]) . "</li>\n";
                    $i++;
                }
                $out .= "</ol>\n";
                continue;
            }

            // Blank line — paragraph separator
            if (trim($line) === '') {
                $i++;
                continue;
            }

            // Paragraph: collect non-blank lines
            $para = '';
            while ($i < $total && trim($lines[$i]) !== ''
                   && !preg_match('/^(#{2,3} |&gt; |[*\-] |\d+\. )/', $lines[$i])
                   && !str_contains($lines[$i], "\x00CODE")) {
                $para .= ($para !== '' ? ' ' : '') . $lines[$i];
                $i++;
            }
            if ($para !== '') {
                $out .= '<p>' . self::inline($para) . "</p>\n";
            }
        }

        // 6. Clean up code block placeholders that ended up in output
        foreach ($blocks as $ph => $html) {
            $out = str_replace($ph, $html, $out);
        }

        return trim($out);
    }

    /** Apply inline transforms to an already-escaped string. */
    private static function inline(string $s): string
    {
        // Inline code: `code`
        $s = preg_replace('/`([^`]+)`/', '<code>$1</code>', $s);

        // Bold: **text**
        $s = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $s);

        // Italic: *text* or _text_
        $s = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $s);
        $s = preg_replace('/_(.+?)_/', '<em>$1</em>', $s);

        // Images: ![alt](/media/...) — same-origin /media/ paths only
        $s = preg_replace_callback(
            '/!\[([^\]]*)\]\((\/media\/[^)]+)\)/',
            fn($m) => '<img src="' . $m[2] . '" alt="' . $m[1] . '" loading="lazy">',
            $s
        );

        // Links: [text](http/https url)
        $s = preg_replace_callback(
            '/\[([^\]]+)\]\((https?:\/\/[^)]+)\)/',
            fn($m) => '<a href="' . $m[2] . '" rel="noopener">' . $m[1] . '</a>',
            $s
        );

        return $s;
    }
}
