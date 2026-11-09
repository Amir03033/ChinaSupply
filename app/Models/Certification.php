<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 */
class Certification extends Model
{
    protected $fillable = ['code', 'name'];
}
