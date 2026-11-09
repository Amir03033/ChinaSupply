<?php

namespace App\Actions\Pages;

use App\Models\Page;
use App\Models\PageTranslation;
use App\Support\SafeHtml;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SavePage
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, mixed>>  $translations
     */
    public function handle(?Page $page, array $attributes, array $translations): Page
    {
        $default = config('chinasupply.default_locale');
        if (blank($translations[$default]['title'] ?? null)) {
            throw ValidationException::withMessages(["translations.$default.title" => 'A title in the default language is required.']);
        }

        return DB::transaction(function () use ($page, $attributes, $translations) {
            $page ??= new Page;
            $page->fill($attributes)->save();

            foreach ($translations as $locale => $data) {
                $existing = $page->translations()->where('locale', $locale)->first();

                if (blank($data['title'] ?? null)) {
                    $existing?->delete();

                    continue;
                }

                $slug = Str::slug(filled($data['slug'] ?? null) ? $data['slug'] : $data['title']) ?: 'page';
                $candidate = $slug;
                $i = 2;
                while (PageTranslation::where('locale', $locale)->where('slug', $candidate)
                    ->when($existing, fn ($q) => $q->whereKeyNot($existing->id))->exists()) {
                    $candidate = $slug.'-'.$i++;
                }

                $translation = $existing ?? new PageTranslation(['locale' => $locale]);
                $translation->fill([
                    'title' => trim($data['title']),
                    'slug' => $candidate,
                    // Sanitised on save as well as on output.
                    'body' => SafeHtml::clean($data['body'] ?? null),
                    'seo_title' => $data['seo_title'] ?? null,
                    'seo_description' => $data['seo_description'] ?? null,
                ]);
                $page->translations()->save($translation);
            }

            return $page->load('translations');
        });
    }
}
