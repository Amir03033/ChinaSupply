<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $locale
 * @property ?string $description
 * @property ?string $capabilities
 */
class SupplierTranslation extends Model
{
    protected $fillable = ['locale', 'description', 'capabilities'];
}
