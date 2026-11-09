<?php

use App\Support\SafeHtml;

it('keeps allowed formatting', function () {
    expect(SafeHtml::clean('<h2>Titel</h2><p><strong>Vet</strong> en <a href="https://example.com">link</a></p><ul><li>a</li></ul>'))
        ->toBe('<h2>Titel</h2><p><strong>Vet</strong> en <a href="https://example.com" rel="noopener nofollow">link</a></p><ul><li>a</li></ul>');
});

it('removes scripts, event handlers, styles and javascript urls', function () {
    $out = SafeHtml::clean('<p style="x" onmouseover="a()">t</p><script>x()</script><iframe src="x"></iframe><a href="javascript:x()">l</a><img src=x onerror=y>');

    expect($out)->toBe('<p>t</p><a>l</a>');
});

it('unwraps unknown tags but keeps their text', function () {
    expect(SafeHtml::clean('<div><span>Tekst</span></div>'))->toBe('Tekst');
});

it('handles empty input', function () {
    expect(SafeHtml::clean(null))->toBe('')->and(SafeHtml::clean(''))->toBe('');
});
