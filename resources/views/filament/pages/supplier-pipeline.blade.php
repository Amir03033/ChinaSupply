<x-filament-panels::page>
    <div class="flex items-center gap-3">
        <x-filament::button size="sm" :color="$mine ? 'gray' : 'primary'" wire:click="$set('mine', false)">All suppliers</x-filament::button>
        <x-filament::button size="sm" :color="$mine ? 'primary' : 'gray'" wire:click="$set('mine', true)">Mine</x-filament::button>
        <span class="text-sm text-gray-500 dark:text-gray-400">Drag a card to another column to change its status. Rejected and inactive suppliers are in the supplier list.</span>
    </div>

    <div class="flex gap-4 overflow-x-auto pb-4" x-data="{ dragging: null }">
        @foreach ($this->getColumns() as $key => $column)
            <section class="flex w-72 shrink-0 flex-col rounded-xl bg-gray-50 p-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10"
                     x-on:dragover.prevent
                     x-on:drop.prevent="if (dragging) { $wire.move(dragging, '{{ $key }}'); dragging = null }"
                     aria-label="{{ $column['status']->getLabel() }}">
                <header class="mb-3 flex items-center justify-between px-1">
                    <h2 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $column['status']->getLabel() }}</h2>
                    <x-filament::badge color="gray">{{ $column['count'] }}</x-filament::badge>
                </header>
                <div class="flex min-h-16 flex-col gap-2">
                    @forelse ($column['suppliers'] as $supplier)
                        <a href="{{ $this->editUrl($supplier) }}" draggable="true" wire:key="card-{{ $supplier->id }}"
                           x-on:dragstart="dragging = {{ $supplier->id }}"
                           class="block cursor-grab rounded-lg bg-white p-3 shadow-sm ring-1 ring-gray-950/5 hover:ring-primary-500 dark:bg-gray-900 dark:ring-white/10">
                            <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $supplier->name }}</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ collect([$supplier->city, $supplier->province])->filter()->implode(', ') ?: '—' }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-1.5 text-xs">
                                <x-filament::badge size="sm" :color="$supplier->verification_level >= 2 ? 'success' : ($supplier->verification_level === 1 ? 'info' : 'gray')">L{{ $supplier->verification_level }}</x-filament::badge>
                                @if ($supplier->next_follow_up_at)
                                    <x-filament::badge size="sm" :color="$supplier->next_follow_up_at->isPast() ? 'danger' : 'gray'">{{ $supplier->next_follow_up_at->format('d M') }}</x-filament::badge>
                                @endif
                                @if ($supplier->assignee)
                                    <span class="text-gray-500 dark:text-gray-400">{{ $supplier->assignee->name }}</span>
                                @endif
                            </div>
                        </a>
                    @empty
                        <p class="px-1 text-xs text-gray-400">No suppliers</p>
                    @endforelse
                    @if ($column['count'] > count($column['suppliers']))
                        <p class="px-1 text-xs text-gray-500">+{{ $column['count'] - count($column['suppliers']) }} more in the supplier list</p>
                    @endif
                </div>
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
