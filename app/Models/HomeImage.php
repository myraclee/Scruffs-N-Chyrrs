<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * HomeImage Model
 * Represents images displayed on the home page
 * 
 * @property int $id
 * @property string $image_path
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class HomeImage extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'image_path',
        'sort_order',
    ];
}
