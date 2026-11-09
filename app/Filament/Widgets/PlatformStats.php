<?php

namespace App\Filament\Widgets;

use App\Enums\EnquiryStatus;
use App\Enums\PublicationStatus;
use App\Enums\UserType;
use App\Models\Category;
use App\Models\ContactEnquiry;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Real database counts only. Phases 2–4 add supplier, request and payment metrics.
 */
class PlatformStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $newEnquiries = ContactEnquiry::where('status', EnquiryStatus::New)->count();
        $enquiries30d = ContactEnquiry::where('created_at', '>=', now()->subDays(30))->count();

        return [
            Stat::make('New contact enquiries', $newEnquiries)
                ->description($enquiries30d.' received in the last 30 days')
                ->color($newEnquiries > 0 ? 'warning' : 'gray'),
            Stat::make('Registered buyers', User::where('type', UserType::Buyer)->count())
                ->description(User::where('type', UserType::Buyer)->where('created_at', '>=', now()->subDays(30))->count().' in the last 30 days'),
            Stat::make('Active categories', Category::where('status', PublicationStatus::Active)->count())
                ->description(Category::whereNull('parent_id')->where('status', PublicationStatus::Active)->count().' top-level'),
        ];
    }
}
