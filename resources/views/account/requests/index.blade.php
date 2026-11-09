<x-layouts.app :title="__('account.nav.requests')" noindex>
    <x-container class="py-12">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <x-section-header as="h1" :title="__('account.nav.requests')" />
            <x-button :href="localized_route('sourcing.create')">{{ __('ui.cta.start_request') }}</x-button>
        </div>
        <x-account-nav class="mt-8" />
        <div class="mt-6 flex gap-2 text-sm">
            @foreach (['open', 'closed'] as $f)
                <a href="{{ route('account.requests.index', $f === 'closed' ? ['filter' => 'closed'] : []) }}" @class(['rounded-full border px-3 py-1', 'border-brand-500 text-ink-50' => $filter === $f, 'border-ink-600 text-ink-300' => $filter !== $f])>{{ __('account.filter.'.$f) }}</a>
            @endforeach
        </div>
        @if ($requests->isEmpty())
            <x-empty-state class="mt-6" icon="heroicon-o-clipboard-document-list" :title="__('account.requests_title')">{{ __('account.requests_text') }}</x-empty-state>
        @else
            <div class="mt-6 overflow-x-auto rounded-[var(--radius-card)] border border-ink-700">
                <table class="w-full min-w-[36rem] text-left text-sm">
                    <thead class="bg-ink-850 text-ink-400"><tr>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('requests.fields.title') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('requests.reference') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('requests.submitted_on') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('requests.status') }}</th>
                    </tr></thead>
                    <tbody class="divide-y divide-ink-700">
                        @foreach ($requests as $r)
                            <tr>
                                <td class="px-4 py-3"><a href="{{ route('account.requests.show', $r->reference) }}" class="font-medium text-ink-50 hover:text-brand-300">{{ $r->title }}</a></td>
                                <td class="px-4 py-3 font-mono text-xs text-ink-300">{{ $r->reference }}</td>
                                <td class="px-4 py-3 text-ink-300">{{ $r->submitted_at->translatedFormat('j M Y') }}</td>
                                <td class="px-4 py-3"><x-request-status :status="$r->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6">{{ $requests->links() }}</div>
        @endif
    </x-container>
</x-layouts.app>
