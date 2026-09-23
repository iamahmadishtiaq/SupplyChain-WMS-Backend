<?php

namespace App\Services;
use App\Models\Product;
use Illuminate\Support\Str;

class SkuGeneratorService
{
    /**
     * Create a new class instance.
     */
    public static function generate(Product $product, array $attributes = []): string
    {
        $categoryCode = strtoupper($product->category->code ?? 'GEN');
        $productShort = strtoupper(substr(STR::slug($product->name, ''), 0, 4));

        $attributeParts = [];
        if (!empty($attributes['colors'])) {
            $attributeParts[] = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $attributes['color']), 0, 3));
        }
        if (!empty($attributes['size'])) {
            $attributeParts[] = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $attributes['size']), 0, 3));
        }

        $attrString = !empty($attributeParts) ? '-' . implode('-', $attributeParts) : '';
        $uniqueSuffix = strtoupper(Str::random(3));

        return "{$categoryCode}-{$productShort}{$attrString}-{$uniqueSuffix}";
    }
}
