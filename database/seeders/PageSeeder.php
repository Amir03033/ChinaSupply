<?php

namespace Database\Seeders;

use App\Enums\PublicationStatus;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Creates the legal and informational pages as structured drafts. The texts
 * are outlines for a lawyer to complete; every page is flagged
 * requires_legal_review, which shows a visible notice on the public page.
 * Existing pages are never overwritten.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $sort => $page) {
            if (Page::where('key', $page['key'])->exists()) {
                continue;
            }

            $model = Page::create([
                'key' => $page['key'],
                'status' => PublicationStatus::Active,
                'requires_legal_review' => $page['legal'],
                'show_in_footer' => true,
                'sort_order' => $sort,
            ]);

            foreach (['nl', 'en'] as $locale) {
                $model->translations()->create([
                    'locale' => $locale,
                    'title' => $page[$locale][0],
                    'slug' => $page[$locale][1],
                    'body' => $this->outline($page[$locale][2]),
                ]);
            }
        }
    }

    /**
     * @param  list<string>  $headings
     */
    private function outline(array $headings): string
    {
        return collect($headings)
            ->map(fn (string $h) => '<h2>'.e($h).'</h2><p>[…]</p>')
            ->implode('');
    }

    /**
     * @return list<array{key: string, legal: bool, nl: array{0: string, 1: string, 2: list<string>}, en: array{0: string, 1: string, 2: list<string>}}>
     */
    private function pages(): array
    {
        return [
            ['key' => 'terms', 'legal' => true,
                'nl' => ['Algemene voorwaarden', 'algemene-voorwaarden', ['Toepasselijkheid', 'Dienstverlening van ChinaSupply', 'Inspanningsverplichting', 'Geen partij bij koopovereenkomsten tussen koper en leverancier', 'Prijzen en betaling', 'Aansprakelijkheid', 'Intellectueel eigendom', 'Toepasselijk recht en geschillen']],
                'en' => ['Terms and conditions', 'terms-and-conditions', ['Applicability', 'ChinaSupply services', 'Best-efforts obligation', 'Not a party to purchase agreements between buyer and supplier', 'Prices and payment', 'Liability', 'Intellectual property', 'Governing law and disputes']]],
            ['key' => 'privacy', 'legal' => true,
                'nl' => ['Privacyverklaring', 'privacyverklaring', ['Wie wij zijn', 'Welke gegevens wij verwerken', 'Doelen en grondslagen', 'Delen met leveranciers en verwerkers', 'Doorgifte buiten de EER', 'Bewaartermijnen', 'Jouw rechten', 'Contact en klachten']],
                'en' => ['Privacy policy', 'privacy-policy', ['Who we are', 'Which data we process', 'Purposes and legal bases', 'Sharing with suppliers and processors', 'Transfers outside the EEA', 'Retention periods', 'Your rights', 'Contact and complaints']]],
            ['key' => 'cookies', 'legal' => true,
                'nl' => ['Cookiebeleid', 'cookiebeleid', ['Welke cookies wij gebruiken', 'Functionele cookies', 'Analytics', 'Cookies beheren']],
                'en' => ['Cookie policy', 'cookie-policy', ['Which cookies we use', 'Functional cookies', 'Analytics', 'Managing cookies']]],
            ['key' => 'payment_terms', 'legal' => true,
                'nl' => ['Betalingsvoorwaarden', 'betalingsvoorwaarden', ['Betaalmethoden', 'Facturatie en btw', 'Moment van betaling', 'Mislukte of geannuleerde betalingen']],
                'en' => ['Payment terms', 'payment-terms', ['Payment methods', 'Invoicing and VAT', 'When payment is due', 'Failed or cancelled payments']]],
            ['key' => 'refund_policy', 'legal' => true,
                'nl' => ['Annulering en terugbetaling', 'annulering-en-terugbetaling', ['Annuleren voor de start van de opdracht', 'Annuleren tijdens de opdracht', 'Terugbetalingen', 'Uitzonderingen']],
                'en' => ['Cancellation and refunds', 'cancellation-and-refunds', ['Cancelling before work starts', 'Cancelling during an assignment', 'Refunds', 'Exceptions']]],
            ['key' => 'supplier_agreement', 'legal' => true,
                'nl' => ['Leveranciersovereenkomst', 'leveranciersovereenkomst', ['Juistheid van gegevens', 'Toestemming voor verificatie en publicatie', 'Gedragsregels', 'Opschorting en beëindiging']],
                'en' => ['Supplier agreement', 'supplier-agreement', ['Accuracy of information', 'Consent to verification and publication', 'Code of conduct', 'Suspension and termination']]],
            ['key' => 'verification_disclaimer', 'legal' => true,
                'nl' => ['Verificatie-disclaimer', 'verificatie-disclaimer', ['Wat verificatie betekent', 'Wat verificatie niet betekent', 'Geldigheid en herziening', 'Onafhankelijkheid van betaalde zichtbaarheid']],
                'en' => ['Verification disclaimer', 'verification-disclaimer', ['What verification means', 'What verification does not mean', 'Validity and review', 'Independence from paid visibility']]],
        ];
    }
}
