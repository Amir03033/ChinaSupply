<x-layouts.app :title="$translation->seo_title ?: $translation->title" :description="$translation->seo_description" :alternates="$alternates">
    <x-container class="py-14">
        <article class="max-w-3xl">
            <h1 class="text-3xl font-semibold tracking-tight text-ink-50 sm:text-4xl">{{ $translation->title }}</h1>
            <p class="mt-3 text-sm text-ink-400">{{ __('pages.cms.updated', ['date' => $translation->updated_at->translatedFormat('j F Y')]) }}</p>
            @if ($page->requires_legal_review)
                <x-alert type="warning" class="mt-6">{{ __('pages.cms.legal_review_notice') }}</x-alert>
            @endif
            <div class="prose-cs mt-8">{!! \App\Support\SafeHtml::clean($translation->body) !!}</div>
        </article>
    </x-container>
</x-layouts.app>
