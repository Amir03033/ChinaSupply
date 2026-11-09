<?php

namespace App\Filament\Resources\ContactEnquiries\Pages;

use App\Filament\Resources\ContactEnquiries\ContactEnquiryResource;
use Filament\Resources\Pages\ViewRecord;

class ViewContactEnquiry extends ViewRecord
{
    protected static string $resource = ContactEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [ContactEnquiryResource::statusAction()];
    }
}
