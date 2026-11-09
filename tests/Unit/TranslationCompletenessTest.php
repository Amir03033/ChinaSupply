<?php

/**
 * Every translation key must exist in every supported locale, so no page
 * silently shows a raw key in one language.
 */
function flattenKeys(array $array, string $prefix = ''): array
{
    $keys = [];
    foreach ($array as $key => $value) {
        $full = $prefix === '' ? (string) $key : $prefix.'.'.$key;
        if (is_array($value) && $value !== [] && ! array_is_list($value)) {
            $keys = array_merge($keys, flattenKeys($value, $full));
        } else {
            $keys[] = $full;
        }
    }

    return $keys;
}

it('has the same translation keys in every locale', function (string $file) {
    $locales = array_keys(config('chinasupply.locales'));
    $sets = [];
    foreach ($locales as $locale) {
        $path = lang_path("$locale/$file");
        expect(file_exists($path))->toBeTrue("Missing lang/$locale/$file");
        $sets[$locale] = flattenKeys(require $path);
    }

    foreach ($locales as $a) {
        foreach ($locales as $b) {
            expect(array_values(array_diff($sets[$a], $sets[$b])))->toBe([], "Keys in $a/$file missing from $b/$file");
        }
    }
})->with(fn () => collect(glob(dirname(__DIR__, 2).'/lang/nl/*.php'))->map(fn ($p) => basename($p))
    ->reject(fn ($f) => $f === 'validation.php') // nl keeps a subset of Laravel's rules
    ->values()->all());
