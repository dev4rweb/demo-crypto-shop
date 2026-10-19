<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'slug',
        'type',
        'price_usd_cents',
        'stock',
        'is_digital',
        'is_featured',
        'bento_size',
        'image',
        'gallery',
        'delivery_payload',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_digital' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'gallery' => 'array',
            'delivery_payload' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function localizedName(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $key = 'app.products.'.$this->slug.'.name';
        $value = trans($key, [], $locale);

        return $value === $key ? $this->slug : $value;
    }

    public function localizedDescription(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $key = 'app.products.'.$this->slug.'.description';
        $value = trans($key, [], $locale);

        return $value === $key ? '' : $value;
    }

    public function toCardArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->localizedName(),
            'description' => $this->localizedDescription(),
            'type' => $this->type,
            'price_usd_cents' => $this->price_usd_cents,
            'price_label' => format_usd($this->price_usd_cents),
            'stock' => $this->stock,
            'is_digital' => $this->is_digital,
            'is_featured' => $this->is_featured,
            'bento_size' => $this->bento_size,
            'image' => $this->image,
            'gallery' => $this->gallery ?? [],
            'category' => $this->category?->slug,
            'category_name' => $this->category?->localizedName(),
            'variants' => $this->variants->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'name' => $variant->name,
                'price_usd_cents' => $variant->price_usd_cents,
                'price_label' => format_usd($variant->price_usd_cents),
                'stock' => $variant->stock,
            ])->values()->all(),
        ];
    }
}
