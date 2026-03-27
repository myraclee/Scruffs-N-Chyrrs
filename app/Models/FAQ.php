<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $faq_category_id
 * @property string $question
 * @property string $answer
 * @property int $sort_order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read FAQCategory $category
 */
class FAQ extends Model
{
    /** @use HasFactory<\Database\Factories\FAQFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'faqs';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'faq_category_id',
        'question',
        'answer',
        'sort_order',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the category this FAQ belongs to.
     */
    public function categoryRelation(): BelongsTo
    {
        return $this->belongsTo(FAQCategory::class, 'faq_category_id');
    }

    /**
     * Scope to get only active FAQs.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Scope to get FAQs by category ID.
     */
    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('faq_category_id', $categoryId);
    }
}
