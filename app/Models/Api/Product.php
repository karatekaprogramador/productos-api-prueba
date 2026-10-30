<?php

namespace App\Models\Api;

use App\Models\Api\Currency\Divisas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    public $table = 'products';

    protected $fillable = [
        'id',
        'name',
        'description',
        'price',
        'tax_cost',
        'manufacturing_cost',
        'divisa_id',
        'created_at',
        'updated_at',
    ];

    public function divisa()
    {
        return $this->belongsTo(Divisas::class);
    }
}
