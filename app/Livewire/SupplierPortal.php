<?php

namespace App\Livewire;

use App\Actions\Suppliers\StoreSupplierDocument;
use App\Actions\Suppliers\SubmitSupplierOnboarding;
use App\Actions\Suppliers\UpdateSupplierProfile;
use App\Enums\CompanyType;
use App\Enums\DocumentType;
use App\Enums\PublicationStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierDocument;
use App\Support\Money;
use App\Support\SupplierOptions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * @property-read Supplier $supplier
 * @property-read Collection<int, Category> $categoryTree
 * @property-read list<string> $missing
 *
 * Supplier onboarding + profile management. Every field autosaves on blur
 * through UpdateSupplierProfile (whitelisted fields only).
 */
class SupplierPortal extends Component
{
    use WithFileUploads;

    public const STEPS = ['company', 'location', 'categories', 'production', 'documents', 'products', 'review'];

    public const MAX_CATEGORIES = 10;

    #[Locked]
    public int $supplierId;

    #[Url(as: 'step')]
    public string $step = 'company';

    /** @var array<string, mixed> */
    public array $form = [];

    public string $description = '';

    public string $capabilities = '';

    /** @var list<int> */
    public array $categoryIds = [];

    public string $documentType = 'business_license';

    public ?string $documentValidUntil = null;

    /** @var mixed */
    public $documentUpload;

    /** @var array<string, mixed> */
    public array $product = [];

    /** @var mixed */
    public $productImage;

    public ?string $savedAt = null;

    /** @var list<string> */
    public array $resetChecks = [];

    public function mount(): void
    {
        $supplier = auth()->user()?->supplier();
        abort_unless($supplier !== null, 403);
        $this->supplierId = $supplier->id;

        $this->form = collect($supplier->only(Supplier::SUPPLIER_EDITABLE))
            ->map(fn ($v) => $v instanceof CompanyType ? $v->value : $v)
            ->all();
        $this->form['export_markets'] ??= [];
        $this->form['languages'] ??= [];
        $translation = $supplier->translations()->where('locale', 'en')->first();
        $this->description = (string) $translation?->description;
        $this->capabilities = (string) $translation?->capabilities;
        $this->categoryIds = $supplier->categories()->pluck('categories.id')->all();
        $this->resetProduct();

        if (! in_array($this->step, self::STEPS, true)) {
            $this->step = 'company';
        }
    }

    #[Computed]
    public function supplier(): Supplier
    {
        $supplier = Supplier::with(['verificationChecks', 'documents', 'products'])->findOrFail($this->supplierId);
        Gate::authorize('updateOwnProfile', $supplier);

        return $supplier;
    }

    /** @return array<string, mixed> */
    protected function fieldRules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:190'],
            'form.name_local' => ['nullable', 'string', 'max:190'],
            'form.legal_name' => ['nullable', 'string', 'max:190'],
            'form.registration_number' => ['nullable', 'string', 'max:64', Rule::unique('suppliers', 'registration_number')->ignore($this->supplierId)],
            'form.company_type' => ['required', Rule::enum(CompanyType::class)],
            'form.founded_year' => ['nullable', 'integer', 'min:1900', 'max:'.date('Y')],
            'form.employee_range' => ['nullable', Rule::in(array_keys(SupplierOptions::employeeRanges()))],
            'form.website' => ['nullable', 'url', 'max:255'],
            'form.country' => ['required', Rule::in(array_keys(SupplierOptions::countries()))],
            'form.province' => ['nullable', 'string', 'max:100'],
            'form.city' => ['nullable', 'string', 'max:100'],
            'form.address' => ['nullable', 'string', 'max:500'],
            'form.business_email' => ['nullable', 'email', 'max:190'],
            'form.business_phone' => ['nullable', 'string', 'max:50'],
            'form.moq_min' => ['nullable', 'integer', 'min:1', 'max:100000000'],
            'form.moq_unit' => ['nullable', 'string', 'max:30'],
            'form.production_capacity' => ['nullable', 'string', 'max:190'],
            'form.lead_time_min_days' => ['nullable', 'integer', 'min:1', 'max:730'],
            'form.lead_time_max_days' => ['nullable', 'integer', 'min:1', 'max:730', 'gte:form.lead_time_min_days'],
            'form.offers_oem' => ['boolean'],
            'form.offers_odm' => ['boolean'],
            'form.offers_private_label' => ['boolean'],
            'form.export_markets' => ['array'],
            'form.export_markets.*' => [Rule::in(array_keys(SupplierOptions::exportMarkets()))],
            'form.languages' => ['array'],
            'form.languages.*' => [Rule::in(array_keys(SupplierOptions::languages()))],
            'description' => ['nullable', 'string', 'max:3000'],
            'capabilities' => ['nullable', 'string', 'max:3000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return collect(trans('portal.fields'))->mapWithKeys(fn ($label, $key) => ["form.$key" => mb_strtolower($label)])->all()
            + ['description' => mb_strtolower(__('portal.fields.description'))];
    }

    /** Autosave: validate and persist the single field that changed. */
    public function updated(string $property): void
    {
        $key = Str::before($property, '.') === 'form' ? 'form.'.Str::between($property.'.', 'form.', '.') : $property;

        if (! array_key_exists($key, $this->fieldRules())) {
            return;
        }

        $rules = [$key => $this->fieldRules()[$key]];
        if (str_ends_with($key, 'export_markets') || str_ends_with($key, 'languages')) {
            $rules[$key.'.*'] = $this->fieldRules()[$key.'.*'];
        }
        if ($key === 'form.lead_time_max_days') {
            $rules['form.lead_time_min_days'] = $this->fieldRules()['form.lead_time_min_days'];
        }
        $this->validate($rules);

        $attributes = [];
        $translations = [];
        if (str_starts_with($key, 'form.')) {
            $field = substr($key, 5);
            $value = $this->form[$field];
            $attributes[$field] = $value === '' ? null : $value;
        } else {
            $translations['en'] = ['description' => $this->description ?: null, 'capabilities' => $this->capabilities ?: null];
        }

        $reset = app(UpdateSupplierProfile::class)->handle($this->supplier, $attributes, $translations);
        $this->resetChecks = array_values(array_unique([...$this->resetChecks, ...array_map(fn ($t) => $t->value, $reset)]));
        unset($this->supplier);
        $this->savedAt = now()->format('H:i');
    }

    public function toggleCategory(int $id): void
    {
        if (in_array($id, $this->categoryIds, true)) {
            $this->categoryIds = array_values(array_diff($this->categoryIds, [$id]));
        } else {
            if (count($this->categoryIds) >= self::MAX_CATEGORIES) {
                $this->addError('categoryIds', __('portal.errors.max_categories', ['max' => self::MAX_CATEGORIES]));

                return;
            }
            abort_unless(Category::active()->whereKey($id)->exists(), 422);
            $this->categoryIds[] = $id;
        }
        $this->supplier->categories()->sync($this->categoryIds);
        $this->savedAt = now()->format('H:i');
    }

    public function uploadDocument(StoreSupplierDocument $store): void
    {
        $this->validate([
            'documentType' => ['required', Rule::in(array_map(fn ($t) => $t->value, DocumentType::supplierUploadable()))],
            'documentUpload' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
            'documentValidUntil' => ['nullable', 'date', 'after:today'],
        ]);

        try {
            $store->handle($this->supplier, $this->documentUpload, DocumentType::from($this->documentType), auth()->user(), $this->documentValidUntil);
        } catch (ValidationException $e) {
            // Content-based MIME check in the action is authoritative.
            $this->addError('documentUpload', collect($e->errors())->flatten()->first());

            return;
        }
        $this->reset('documentUpload', 'documentValidUntil');
        unset($this->supplier);
        session()->flash('portal_status', __('portal.documents.uploaded'));
    }

    public function deleteDocument(int $id, StoreSupplierDocument $store): void
    {
        $document = SupplierDocument::where('supplier_id', $this->supplierId)->findOrFail($id);
        Gate::authorize('delete', $document);
        $store->delete($document);
        unset($this->supplier);
    }

    public function saveProduct(): void
    {
        $this->validate([
            'product.name' => ['required', 'string', 'max:190'],
            'product.category_id' => ['nullable', Rule::exists('categories', 'id')->where('status', PublicationStatus::Active->value)],
            'product.description' => ['nullable', 'string', 'max:3000'],
            'product.moq' => ['nullable', 'integer', 'min:1'],
            'product.price_min' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'product.price_max' => ['nullable', 'numeric', 'min:0', 'max:1000000', 'gte:product.price_min'],
            'product.currency' => ['required', Rule::in(['USD', 'EUR', 'CNY'])],
            'productImage' => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
        ], [], ['product.name' => mb_strtolower(__('portal.products.name'))]);

        $product = $this->product['id'] ?? null
            ? Product::where('supplier_id', $this->supplierId)->findOrFail($this->product['id'])
            : new Product(['supplier_id' => $this->supplierId]);
        $product->supplier_id = $this->supplierId;
        $product->fill([
            'name' => $this->product['name'],
            'category_id' => $this->product['category_id'] ?: null,
            'description' => $this->product['description'] ?: null,
            'moq' => $this->product['moq'] ?: null,
            'price_min_cents' => Money::decimalToCents($this->product['price_min'] ?? null),
            'price_max_cents' => Money::decimalToCents($this->product['price_max'] ?? null),
            'currency' => $this->product['currency'],
        ]);
        if ($this->productImage) {
            $product->image_path = $this->productImage->store('products', 'public');
        }
        $product->save();

        $this->resetProduct();
        unset($this->supplier);
    }

    public function editProduct(int $id): void
    {
        $p = Product::where('supplier_id', $this->supplierId)->findOrFail($id);
        $this->product = [
            'id' => $p->id, 'name' => $p->name, 'category_id' => $p->category_id, 'description' => $p->description, 'moq' => $p->moq,
            'price_min' => Money::centsToDecimal($p->price_min_cents), 'price_max' => Money::centsToDecimal($p->price_max_cents), 'currency' => $p->currency,
        ];
    }

    public function deleteProduct(int $id): void
    {
        Product::where('supplier_id', $this->supplierId)->findOrFail($id)->delete();
        unset($this->supplier);
    }

    public function resetProduct(): void
    {
        $this->product = ['id' => null, 'name' => '', 'category_id' => null, 'description' => '', 'moq' => null, 'price_min' => null, 'price_max' => null, 'currency' => 'USD'];
        $this->productImage = null;
    }

    public function submit(SubmitSupplierOnboarding $submit): void
    {
        try {
            $submit->handle($this->supplier);
            unset($this->supplier);
            session()->flash('portal_status', __('portal.review.submitted'));
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
        }
    }

    public function goTo(string $step): void
    {
        if (in_array($step, self::STEPS, true)) {
            $this->step = $step;
        }
    }

    /** @return Collection<int, Category> */
    #[Computed]
    public function categoryTree(): Collection
    {
        return Category::active()->roots()->withCurrentTranslation()
            ->with(['children' => fn ($q) => $q->active()->withCurrentTranslation()])
            ->orderBy('sort_order')->get();
    }

    /** @return list<string> */
    #[Computed]
    public function missing(): array
    {
        return SubmitSupplierOnboarding::missing($this->supplier);
    }

    public function render()
    {
        return view('livewire.supplier-portal');
    }
}
