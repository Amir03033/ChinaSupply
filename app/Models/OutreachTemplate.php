<?php

namespace App\Models;

use App\Enums\CommunicationChannel;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property CommunicationChannel $channel
 * @property string $locale
 * @property ?string $subject
 * @property string $body
 * @property bool $is_active
 */
class OutreachTemplate extends Model
{
    protected $fillable = ['name', 'channel', 'locale', 'subject', 'body', 'is_active'];

    protected function casts(): array
    {
        return ['channel' => CommunicationChannel::class, 'is_active' => 'boolean'];
    }

    /**
     * Fill {placeholders}. Unknown placeholders are left visible on purpose so
     * staff notice them before sending.
     *
     * @return array{subject: ?string, body: string}
     */
    public function render(Supplier $supplier, ?SupplierContact $contact, ?User $sender): array
    {
        $vars = [
            '{company}' => $supplier->name,
            '{contact_name}' => $contact->name ?? 'Sir/Madam',
            '{sender_name}' => $sender->name ?? 'ChinaSupply',
            '{company_kvk}' => (string) config('chinasupply.company.kvk'),
            '{website}' => rtrim((string) config('app.url'), '/'),
        ];

        return [
            'subject' => $this->subject ? strtr($this->subject, $vars) : null,
            'body' => strtr($this->body, $vars),
        ];
    }
}
