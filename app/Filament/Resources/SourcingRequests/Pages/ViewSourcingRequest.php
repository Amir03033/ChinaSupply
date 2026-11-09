<?php

namespace App\Filament\Resources\SourcingRequests\Pages;

use App\Actions\Sourcing\ChangeRequestStatus;
use App\Actions\Sourcing\PostRequestMessage;
use App\Actions\Sourcing\RecordRequestEvent;
use App\Actions\Sourcing\ShareShortlist;
use App\Enums\SourcingRequestStatus;
use App\Enums\UserType;
use App\Filament\Resources\SourcingRequests\SourcingRequestResource;
use App\Models\SourcingRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class ViewSourcingRequest extends ViewRecord
{
    protected static string $resource = SourcingRequestResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        // Opening the request marks the buyer's messages as read for staff.
        $this->request()->messages()->where('from_staff', false)->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function getTitle(): string
    {
        return $this->request()->reference.' — '.$this->request()->title;
    }

    private function request(): SourcingRequest
    {
        $record = $this->getRecord();

        return $record instanceof SourcingRequest ? $record : throw new \LogicException;
    }

    protected function getHeaderActions(): array
    {
        $request = $this->request();

        return [
            Action::make('assign_me')->label('Assign to me')->icon(Heroicon::OutlinedUserPlus)->color('gray')
                ->visible($request->assigned_to !== auth()->id())
                ->action(function () use ($request) {
                    $request->assigned_to = auth()->id();
                    $request->save();
                    RecordRequestEvent::record($request, 'assigned', ['name' => auth()->user()->name], auth()->user(), false);
                    if ($request->status === SourcingRequestStatus::Submitted) {
                        app(ChangeRequestStatus::class)->handle($request, SourcingRequestStatus::InReview, auth()->user());
                    }
                    Notification::make()->title('Assigned to you')->success()->send();
                }),
            Action::make('reassign')->label('Assign…')->icon(Heroicon::OutlinedUsers)->color('gray')
                ->schema([Select::make('assigned_to')->options(fn () => User::where('type', UserType::Staff)->pluck('name', 'id'))->required()])
                ->action(function (array $data) use ($request) {
                    $request->assigned_to = (int) $data['assigned_to'];
                    $request->save();
                    RecordRequestEvent::record($request, 'assigned', ['name' => User::find($data['assigned_to'])?->name], auth()->user(), false);
                }),
            Action::make('status')->label('Change status')->icon(Heroicon::OutlinedArrowsRightLeft)
                ->fillForm(['status' => $request->status->value])
                ->schema([
                    Select::make('status')->options(SourcingRequestStatus::class)->required(),
                    Textarea::make('message')->label('Message to the buyer (optional, sent in the request thread)')->rows(4)
                        ->helperText('Required in practice for "Needs info": tell the buyer exactly what you need.'),
                ])
                ->action(function (array $data) use ($request) {
                    $to = $data['status'] instanceof SourcingRequestStatus ? $data['status'] : SourcingRequestStatus::from($data['status']);
                    app(ChangeRequestStatus::class)->handle($request, $to, auth()->user(), $data['message'] ?? null);
                    Notification::make()->title('Status updated, buyer notified')->success()->send();
                }),
            Action::make('reply')->label('Message buyer')->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->schema([Textarea::make('body')->label('Message')->required()->rows(6)->maxLength(5000)])
                ->action(function (array $data) use ($request) {
                    app(PostRequestMessage::class)->handle($request, auth()->user(), $data['body']);
                    Notification::make()->title('Message sent')->success()->send();
                }),
            Action::make('share')->label('Share shortlist')->icon(Heroicon::OutlinedPaperAirplane)->color('success')
                ->modalDescription('The buyer sees the selected suppliers with their quote summary and your advice. Only published, verified suppliers can be shared.')
                ->schema([
                    CheckboxList::make('matches')->required()
                        ->options(fn () => $request->matches()->with('supplier')->get()
                            ->mapWithKeys(fn ($m) => [$m->id => $m->supplier->name.' — '.$m->status->getLabel().($m->hasQuote() ? ' (quote)' : '').($m->shared_with_buyer ? ' · already shared' : '')]))
                        ->default(fn () => $request->matches()->where('shared_with_buyer', true)->pluck('id')->all()),
                    Textarea::make('note')->label('Note to the buyer (optional)')->rows(4),
                ])
                ->action(function (array $data) use ($request) {
                    try {
                        app(ShareShortlist::class)->handle($request, array_map('intval', $data['matches']), auth()->user(), $data['note'] ?? null);
                        Notification::make()->title('Shortlist shared with the buyer')->success()->send();
                    } catch (ValidationException $e) {
                        Notification::make()->title('Not shared')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
                    }
                }),
        ];
    }
}
