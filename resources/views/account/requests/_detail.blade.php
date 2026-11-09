{{-- Shared by the buyer dashboard (interactive) and the signed guest tracking page (read-only). --}}
@php($r = $sourcingRequest)
<div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
    <div>
        <p class="font-mono text-xs text-ink-400">{{ $r->reference }}</p>
        <h1 class="mt-1 text-2xl font-semibold text-ink-50 sm:text-3xl">{{ $r->title }}</h1>
        <p class="mt-1 text-sm text-ink-400">{{ __('requests.submitted_at', ['date' => $r->submitted_at->translatedFormat('j F Y')]) }}</p>
    </div>
    <x-request-status :status="$r->status" class="self-start" />
</div>

<div class="surface mt-6 border-brand-500/30 p-5">
    <p class="text-sm text-ink-400">{{ __('requests.next_title') }}</p>
    <p class="mt-1 text-ink-50">{{ $r->status->buyerNextStep() }}</p>
</div>

<div class="mt-8 grid gap-8 lg:grid-cols-3">
    <div class="space-y-10 lg:col-span-2">
        @if ($r->matches->isNotEmpty())
            <section aria-labelledby="shortlist">
                <h2 id="shortlist" class="text-lg font-semibold text-ink-50">{{ __('requests.shortlist.title') }}</h2>
                <p class="mt-1 text-sm text-ink-300">{{ __('requests.shortlist.lead') }}</p>
                <div class="mt-4 overflow-x-auto rounded-[var(--radius-card)] border border-ink-700">
                    <table class="w-full min-w-[42rem] text-left text-sm">
                        <thead class="bg-ink-850 text-ink-400"><tr>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('requests.shortlist.supplier') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('requests.shortlist.price') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">MOQ</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('requests.shortlist.lead_time') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('requests.shortlist.sample') }}</th>
                            @if ($canInteract)<th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('requests.shortlist.choose') }}</span></th>@endif
                        </tr></thead>
                        <tbody class="divide-y divide-ink-700">
                            @foreach ($r->matches as $m)
                                <tr class="align-top">
                                    <td class="px-4 py-3">
                                        @if ($m->supplier->isPubliclyVisible())
                                            <a href="{{ localized_route('suppliers.show', ['slug' => $m->supplier->slug]) }}" class="font-medium text-ink-50 hover:text-brand-300">{{ $m->supplier->name }}</a>
                                        @else
                                            <span class="font-medium text-ink-50">{{ $m->supplier->name }}</span>
                                        @endif
                                        <div class="mt-1"><x-verification-level-badge :level="$m->supplier->verification_level" /></div>
                                        @if ($m->advice)<p class="mt-2 text-xs leading-5 text-ink-300"><span class="text-ink-400">{{ __('requests.shortlist.advice') }}:</span> {{ $m->advice }}</p>@endif
                                    </td>
                                    <td class="px-4 py-3 text-ink-100">{{ $m->unit_price_cents !== null ? \App\Support\Money::format($m->unit_price_cents, $m->currency ?? 'USD') : '—' }}@if ($m->incoterm)<span class="block text-xs text-ink-400">{{ $m->incoterm }}</span>@endif</td>
                                    <td class="px-4 py-3 text-ink-100">{{ $m->moq ? number_format($m->moq, 0, ',', '.') : '—' }}</td>
                                    <td class="px-4 py-3 text-ink-100">{{ $m->lead_time_days ? __('suppliers.days', ['range' => $m->lead_time_days]) : '—' }}</td>
                                    <td class="px-4 py-3 text-ink-100">{{ $m->sample_cost_cents !== null ? \App\Support\Money::format($m->sample_cost_cents, $m->currency ?? 'USD') : '—' }}</td>
                                    @if ($canInteract)
                                        <td class="px-4 py-3 text-right">
                                            @if ($m->status->value === 'selected')
                                                <span class="text-xs font-medium text-brand-300">{{ __('requests.shortlist.selected') }}</span>
                                            @elseif ($r->status->isOpen())
                                                <form method="POST" action="{{ route('account.requests.select', [$r->reference, $m->id]) }}">@csrf
                                                    <x-button size="sm" variant="secondary" onclick="return confirm(@js(__('requests.shortlist.confirm', ['name' => $m->supplier->name])))">{{ __('requests.shortlist.choose') }}</x-button>
                                                </form>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-xs text-ink-400">{{ __('requests.shortlist.disclaimer') }}</p>
            </section>
        @endif

        <section id="messages" aria-labelledby="msg-title">
            <h2 id="msg-title" class="text-lg font-semibold text-ink-50">{{ __('requests.messages.title') }}</h2>
            @if ($r->messages->isEmpty())
                <p class="mt-3 text-sm text-ink-400">{{ __('requests.messages.empty') }}</p>
            @else
                <ol class="mt-4 space-y-3">
                    @foreach ($r->messages as $msg)
                        <li @class(['rounded-lg border p-4 text-sm', 'border-brand-500/30 bg-brand-500/5' => $msg->from_staff, 'border-ink-700 bg-ink-850 sm:ml-10' => ! $msg->from_staff])>
                            <p class="text-xs text-ink-400">{{ $msg->from_staff ? 'ChinaSupply' : __('requests.messages.you') }} · {{ $msg->created_at->translatedFormat('j M Y, H:i') }}</p>
                            <p class="mt-2 whitespace-pre-line leading-6 text-ink-100">{{ $msg->body }}</p>
                        </li>
                    @endforeach
                </ol>
            @endif
            @if ($canInteract && $r->status->isOpen())
                <form method="POST" action="{{ route('account.requests.message', $r->reference) }}" class="mt-4 space-y-3">
                    @csrf
                    <x-form.textarea name="body" :label="__('requests.fields.message')" required rows="4" />
                    <x-button size="sm">{{ __('requests.messages.send') }}</x-button>
                </form>
            @elseif (! $canInteract)
                <x-alert class="mt-4">{{ __('requests.track.login_hint', ['email' => $r->contact_email]) }}</x-alert>
            @endif
        </section>
    </div>

    <aside class="space-y-6">
        <section class="surface p-5" aria-labelledby="timeline">
            <h2 id="timeline" class="font-semibold text-ink-50">{{ __('requests.timeline') }}</h2>
            <ol class="mt-4 space-y-4 border-l border-ink-700 pl-4">
                @foreach ($r->events as $e)
                    <li class="relative">
                        <span class="absolute -left-[1.3rem] top-1.5 h-2 w-2 rounded-full bg-brand-400" aria-hidden="true"></span>
                        <p class="text-sm text-ink-100">{{ $e->describe() }}</p>
                        <p class="text-xs text-ink-400">{{ $e->created_at->translatedFormat('j M Y, H:i') }}</p>
                    </li>
                @endforeach
            </ol>
        </section>
        <section class="surface p-5 text-sm" aria-labelledby="req-details">
            <h2 id="req-details" class="font-semibold text-ink-50">{{ __('requests.details') }}</h2>
            <dl class="mt-3 space-y-3">
                @foreach (array_filter([
                    __('requests.fields.quantity') => number_format($r->quantity, 0, ',', '.').' '.($r->quantity_unit ?? ''),
                    __('requests.fields.target_price') => $r->target_price_cents !== null ? \App\Support\Money::format($r->target_price_cents, $r->currency) : null,
                    __('requests.fields.category_id') => $r->category?->name(),
                    __('requests.fields.customization') => __('requests.customization.'.$r->customization.'.label'),
                    __('requests.fields.destination_country') => __('requests.countries.'.$r->destination_country),
                    __('requests.fields.incoterm') => $r->incoterm,
                    __('requests.fields.desired_delivery_date') => $r->desired_delivery_date?->translatedFormat('j M Y'),
                ]) as $label => $value)
                    <div><dt class="text-ink-400">{{ $label }}</dt><dd class="text-ink-100">{{ $value }}</dd></div>
                @endforeach
            </dl>
            <details class="mt-4">
                <summary class="cursor-pointer text-brand-400">{{ __('requests.show_description') }}</summary>
                <p class="mt-2 whitespace-pre-line text-ink-200">{{ $r->description }}</p>
                @if ($r->specifications)<p class="mt-2 whitespace-pre-line text-ink-300">{{ $r->specifications }}</p>@endif
            </details>
            @if ($r->files->isNotEmpty())
                <h3 class="mt-4 text-ink-400">{{ __('requests.fields.files') }}</h3>
                <ul class="mt-1 space-y-1">
                    @foreach ($r->files as $f)
                        <li>@if ($canInteract)<a href="{{ route('account.requests.file', [$r->reference, $f->id]) }}" class="text-brand-400 hover:text-brand-300">{{ $f->original_name }}</a>@else<span class="text-ink-200">{{ $f->original_name }}</span>@endif</li>
                    @endforeach
                </ul>
            @endif
        </section>
    </aside>
</div>
