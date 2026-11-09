<?php

namespace Database\Seeders;

use App\Models\OutreachTemplate;
use Illuminate\Database\Seeder;

class OutreachTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (OutreachTemplate::exists()) {
            return;
        }

        $templates = [
            ['First contact', 'European buyers for your products — free supplier verification', <<<'TXT'
Dear {contact_name},

I am {sender_name} from ChinaSupply, a sourcing service registered in the Netherlands (Chamber of Commerce {company_kvk}). We help European businesses find reliable manufacturers.

We would like to include {company} in our checked supplier directory. Listing and verification are free; we never charge suppliers for verification.

Could you please confirm:
1. Do you manufacture in your own factory?
2. What are your main products and minimum order quantities?
3. Do you already export to Europe?

Kind regards,
{sender_name}
ChinaSupply — {website}
TXT],
            ['Follow-up 1 (day 4)', 'Quick question about {company}', <<<'TXT'
Dear {contact_name},

A short follow-up on my previous message: do you manufacture your products in your own factory?

Kind regards,
{sender_name} — ChinaSupply
TXT],
            ['Follow-up 2 (day 10)', 'Buyer enquiries for {company}', <<<'TXT'
Dear {contact_name},

We regularly receive enquiries from European businesses. A verified profile helps us introduce suitable buyers to you. Registration takes about 15 minutes: {website}

(Only mention a specific buyer request if one actually exists.)

Kind regards,
{sender_name} — ChinaSupply
TXT],
            ['Closing (day 21)', 'Closing your file for now', <<<'TXT'
Dear {contact_name},

We have not heard back, so we will close your file for now. You are welcome to reply at any time.

Kind regards,
{sender_name} — ChinaSupply
TXT],
            ['Document request', 'Documents for your ChinaSupply verification', <<<'TXT'
Dear {contact_name},

Thank you for your interest. To verify {company}, please upload the following in your supplier account (or reply with them):

- Business licence (营业执照), clear scan or photo
- Export licence or customs registration, if available
- Certificates (ISO, BSCI, CE test reports, etc.), if available
- 5–10 recent factory photos: exterior, production line, quality control, warehouse
- Product catalogue

We only use these documents to verify your company. They are never shown publicly without your permission.

Kind regards,
{sender_name} — ChinaSupply
TXT],
        ];

        foreach ($templates as [$name, $subject, $body]) {
            OutreachTemplate::create(['name' => $name, 'channel' => 'email', 'locale' => 'en', 'subject' => $subject, 'body' => $body, 'is_active' => true]);
        }
    }
}
