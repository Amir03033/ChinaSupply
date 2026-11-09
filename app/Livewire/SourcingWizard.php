<?php

namespace App\Livewire;

use App\Actions\Sourcing\StoreRequestFile;
use App\Actions\Sourcing\SubmitSourcingRequest;
use App\Enums\PublicationStatus;
use App\Models\Category;
use App\Models\Supplier;
use App\Support\Money;
use App\Support\SourcingOptions;
use App\Support\SupplierDirectory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Guest-friendly sourcing request wizard. No account is needed; a draft is
 * kept in the session so a page reload does not lose the text.
 *
 * @property-read Collection<int, Category> $categoryOptions
 * @property-read ?Supplier $preferredSupplier
 */
class SourcingWizard extends Component
{
    use WithFileUploads;

    public const STEPS = ['product', 'details', 'quantity', 'delivery', 'contact'];

    public const DRAFT_KEY = 'sourcing_draft';

    public int $stepIndex = 0;

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var array<int, mixed> */
    public array $uploads = [];

    public bool $consent = false;

    public string $website = ''; // honeypot

    #[Locked]
    public ?int $preferredSupplierId = null;

    public ?string $submittedReference = null;

    public function mount(): void
    {
        $defaults = [
            'title' => '', 'description' => '', 'category_id' => null, 'specifications' => '', 'materials' => '',
            'customization' => 'unsure', 'required_certifications' => [], 'quantity' => null, 'quantity_unit' => '',
            'target_price' => null, 'currency' => 'EUR', 'desired_delivery_date' => null, 'destination_country' => 'NL',
            'incoterm' => '', 'additional_requirements' => '', 'contact_name' => '', 'contact_email' => '', 'company_name' => '', 'phone' => '',
        ];
        $this->form = array_merge($defaults, array_intersect_key((array) session(self::DRAFT_KEY, []), $defaults));

        if ($user = auth()->user()) {
            $this->form['contact_name'] = $this->form['contact_name'] ?: $user->name;
            $this->form['contact_email'] = $user->email;
        }

        if ($slug = request()->query('supplier')) {
            $this->preferredSupplierId = Supplier::publiclyVisible()->where('slug', (string) $slug)->value('id');
        }
        if (($slug = request()->query('category')) && ($category = SupplierDirectory::category((string) $slug))) {
            $this->form['category_id'] = $category->id;
        }
        if (($q = request()->query('q')) && $this->form['title'] === '') {
            $this->form['title'] = mb_substr((string) $q, 0, 120);
        }
    }

    /** @return array<string, array<string, mixed>> */
    protected function stepRules(): array
    {
        return [
            'product' => [
                'form.title' => ['required', 'string', 'min:3', 'max:120'],
                'form.description' => ['required', 'string', 'min:20', 'max:5000'],
                'form.category_id' => ['nullable', Rule::exists('categories', 'id')->where('status', PublicationStatus::Active->value)],
                'uploads' => ['array', 'max:'.StoreRequestFile::MAX_FILES],
                'uploads.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
            ],
            'details' => [
                'form.specifications' => ['nullable', 'string', 'max:3000'],
                'form.materials' => ['nullable', 'string', 'max:190'],
                'form.customization' => ['required', Rule::in(SourcingOptions::CUSTOMIZATION)],
                'form.required_certifications' => ['array'],
                'form.required_certifications.*' => [Rule::in(SourcingOptions::CERTIFICATIONS)],
            ],
            'quantity' => [
                'form.quantity' => ['required', 'integer', 'min:1', 'max:100000000'],
                'form.quantity_unit' => ['nullable', 'string', 'max:30'],
                'form.target_price' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
                'form.currency' => ['required', Rule::in(SourcingOptions::CURRENCIES)],
                'form.desired_delivery_date' => ['nullable', 'date', 'after:today'],
            ],
            'delivery' => [
                'form.destination_country' => ['required', Rule::in(SourcingOptions::COUNTRIES)],
                'form.incoterm' => ['nullable', Rule::in(SourcingOptions::INCOTERMS)],
                'form.additional_requirements' => ['nullable', 'string', 'max:3000'],
            ],
            'contact' => [
                'form.contact_name' => ['required', 'string', 'max:120'],
                'form.contact_email' => ['required', 'email:rfc', 'max:190'],
                'form.company_name' => ['nullable', 'string', 'max:160'],
                'form.phone' => ['nullable', 'string', 'max:40'],
                'consent' => ['accepted'],
                'website' => ['prohibited'],
            ],
        ];
    }

    protected function validationAttributes(): array
    {
        return collect(trans('requests.fields'))->mapWithKeys(fn ($l, $k) => ["form.$k" => mb_strtolower($l)])->all()
            + ['uploads.*' => mb_strtolower(__('requests.fields.files')), 'consent' => mb_strtolower(__('requests.fields.consent'))];
    }

    public function next(): void
    {
        $this->validate($this->stepRules()[self::STEPS[$this->stepIndex]]);
        $this->saveDraft();
        $this->stepIndex = min($this->stepIndex + 1, count(self::STEPS) - 1);
        $this->dispatch('wizard-step');
    }

    public function back(): void
    {
        $this->saveDraft();
        $this->stepIndex = max($this->stepIndex - 1, 0);
        $this->dispatch('wizard-step');
    }

    public function goTo(int $index): void
    {
        // Only allow jumping back to completed steps.
        if ($index >= 0 && $index < $this->stepIndex) {
            $this->stepIndex = $index;
            $this->dispatch('wizard-step');
        }
    }

    public function removeUpload(int $index): void
    {
        unset($this->uploads[$index]);
        $this->uploads = array_values($this->uploads);
    }

    public function submit(SubmitSourcingRequest $submit): void
    {
        // Validate every step (the client could skip steps).
        foreach (self::STEPS as $i => $step) {
            try {
                $this->validate($this->stepRules()[$step]);
            } catch (ValidationException $e) {
                $this->stepIndex = $i;
                throw $e;
            }
        }

        $key = 'sourcing-request:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('form.contact_email', __('requests.errors.too_many'));

            return;
        }
        RateLimiter::hit($key, 3600);

        $data = $this->form;
        $data['target_price_cents'] = Money::decimalToCents($data['target_price'] ?? null);
        $data['incoterm'] = $data['incoterm'] ?: null;
        $data['desired_delivery_date'] = $data['desired_delivery_date'] ?: null;
        $data['preferred_supplier_id'] = $this->preferredSupplierId;
        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);

        try {
            $request = $submit->handle($data, $this->uploads, auth()->user(), app()->getLocale(), request()->ip());
        } catch (ValidationException $e) {
            $this->stepIndex = 0;
            $this->addError('uploads', collect($e->errors())->flatten()->first());

            return;
        }

        session()->forget(self::DRAFT_KEY);

        if ($request->user_id) {
            session()->flash('status', __('requests.submitted_flash', ['reference' => $request->reference]));
            $this->redirectRoute('account.requests.show', $request->reference);

            return;
        }

        $this->submittedReference = $request->reference;
    }

    private function saveDraft(): void
    {
        session([self::DRAFT_KEY => $this->form]);
    }

    /** @return Collection<int, Category> */
    #[Computed]
    public function categoryOptions(): Collection
    {
        return Category::active()->roots()->withCurrentTranslation()
            ->with(['children' => fn ($q) => $q->active()->withCurrentTranslation()])
            ->orderBy('sort_order')->get();
    }

    #[Computed]
    public function preferredSupplier(): ?Supplier
    {
        return $this->preferredSupplierId ? Supplier::find($this->preferredSupplierId) : null;
    }

    public function render()
    {
        return view('livewire.sourcing-wizard');
    }
}
