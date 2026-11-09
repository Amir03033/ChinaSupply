<?php

namespace App\Http\Controllers;

use App\Enums\ContactTopic;
use App\Enums\EnquiryStatus;
use App\Http\Requests\StoreContactEnquiryRequest;
use App\Models\ContactEnquiry;
use App\Notifications\NewContactEnquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(Request $request): View
    {
        $topic = ContactTopic::tryFrom((string) $request->query('topic')) ?? ContactTopic::General;

        return view('contact', ['topic' => $topic]);
    }

    public function store(StoreContactEnquiryRequest $request): RedirectResponse
    {
        $enquiry = ContactEnquiry::create($request->safe()->only(['topic', 'name', 'email', 'company', 'phone', 'message']) + [
            'locale' => app()->getLocale(),
            'status' => EnquiryStatus::New,
            'ip_address' => $request->ip(),
        ]);

        Notification::route('mail', config('chinasupply.mail.staff_notifications'))
            ->notify(new NewContactEnquiry($enquiry));

        return redirect()->to(localized_route('contact'))->with('status', __('contact.sent'));
    }
}
