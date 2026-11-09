<?php

namespace App\Http\Controllers;

use App\Actions\Sourcing\PostRequestMessage;
use App\Actions\Sourcing\SelectSupplier;
use App\Enums\Permission;
use App\Enums\SourcingRequestStatus;
use App\Models\SourcingRequest;
use App\Models\SourcingRequestFile;
use App\Models\SourcingRequestMatch;
use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SourcingRequestController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user = $request->user();

        return view('account.index', [
            'openRequests' => $user->sourcingRequests()->open()->latest('submitted_at')->limit(5)->get(),
            'actionNeeded' => $user->sourcingRequests()->whereIn('status', ['needs_info', 'shortlist_ready'])->count(),
            'favoriteCount' => $user->favoriteSuppliers()->count(),
            'requestCount' => $user->sourcingRequests()->count(),
        ]);
    }

    public function index(Request $request): View
    {
        $status = $request->query('filter') === 'closed' ? 'closed' : 'open';
        $query = $request->user()->sourcingRequests()->latest('submitted_at');
        $status === 'open' ? $query->open() : $query->whereNotIn('status', array_map(fn ($s) => $s->value, SourcingRequestStatus::open()));

        return view('account.requests.index', ['requests' => $query->paginate(15)->withQueryString(), 'filter' => $status]);
    }

    public function show(Request $request, string $reference): View
    {
        $sourcingRequest = $this->ownedRequest($request, $reference);

        // Opening the request marks ChinaSupply's messages as read.
        $sourcingRequest->messages()->where('from_staff', true)->whereNull('read_at')->update(['read_at' => now()]);

        return view('account.requests.show', $this->detailData($sourcingRequest) + ['canInteract' => true]);
    }

    public function message(Request $request, string $reference, PostRequestMessage $post): RedirectResponse
    {
        $sourcingRequest = $this->ownedRequest($request, $reference);
        $request->validate(['body' => ['required', 'string', 'min:2', 'max:5000']], [], ['body' => __('requests.fields.message')]);

        $post->handle($sourcingRequest, $request->user(), (string) $request->input('body'));

        return redirect()->to(route('account.requests.show', $reference).'#messages')->with('status', __('requests.message_sent'));
    }

    public function select(Request $request, string $reference, SourcingRequestMatch $match, SelectSupplier $select): RedirectResponse
    {
        $sourcingRequest = $this->ownedRequest($request, $reference);
        abort_unless($match->sourcing_request_id === $sourcingRequest->id, 404);

        $select->handle($match, $request->user());

        return redirect()->route('account.requests.show', $reference)->with('status', __('requests.selected_flash', ['name' => $match->supplier->name]));
    }

    public function file(Request $request, string $reference, int $file): StreamedResponse
    {
        $sourcingRequest = $this->ownedRequest($request, $reference);
        $f = $sourcingRequest->files()->findOrFail($file);

        return Storage::disk($f->disk)->download($f->path, $f->original_name, [
            'Content-Type' => $f->mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
        ]);
    }

    public function favorites(Request $request): View
    {
        $suppliers = $request->user()->favoriteSuppliers()->publiclyVisible()->with('translations')->orderBy('name')->get();

        return view('account.favorites', [
            'suppliers' => $suppliers,
            'compareIds' => $request->session()->get('compare', []),
            'favoriteIds' => $suppliers->pluck('id')->all(),
        ]);
    }

    /** Staff download of a buyer's reference file. */
    public function staffFile(Request $request, SourcingRequestFile $file): StreamedResponse
    {
        abort_unless($request->user()?->isStaff() && $request->user()->can(Permission::ManageSourcingRequests->value), 403);

        return Storage::disk($file->disk)->download($file->path, $file->original_name, [
            'Content-Type' => $file->mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
        ]);
    }

    /** Signed, read-only status page for guests (link from the confirmation mail). */
    public function track(SourcingRequest $sourcingRequest): View
    {
        if (Locales::isSupported($sourcingRequest->locale)) {
            app()->setLocale($sourcingRequest->locale);
        }

        return view('account.requests.track', $this->detailData($sourcingRequest) + ['canInteract' => false]);
    }

    private function ownedRequest(Request $request, string $reference): SourcingRequest
    {
        // Not-owned and non-existing look the same (404) to avoid leaking references.
        return SourcingRequest::where('reference', $reference)->where('user_id', $request->user()->id)->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function detailData(SourcingRequest $sourcingRequest): array
    {
        $sourcingRequest->load([
            'category' => fn ($q) => $q->withCurrentTranslation(),
            'files',
            'matches' => fn ($q) => $q->where('shared_with_buyer', true)->with(['supplier.translations']),
            'events' => fn ($q) => $q->where('visible_to_buyer', true),
            'messages.user',
        ]);

        return ['sourcingRequest' => $sourcingRequest];
    }
}
