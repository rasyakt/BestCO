<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class UpdateProductAction
{
    /**
     * Perbarui data master produk (nama, harga jual, HPP, min alert, barcode, status aktif).
     *
     * @param array{
     *     name?: string,
     *     selling_price?: int|float|string,
     *     average_cost?: int|float|string,
     *     min_stock_alert?: int|float|string,
     *     barcode?: ?string,
     *     is_active?: bool
     * } $data
     */
    public function execute(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $updates = [];

            if (isset($data['name'])) {
                $updates['name'] = trim((string) $data['name']);
            }

            if (isset($data['selling_price'])) {
                $updates['selling_price'] = number_format((float) $data['selling_price'], 4, '.', '');
            }

            if (isset($data['average_cost'])) {
                $updates['average_cost'] = number_format((float) $data['average_cost'], 4, '.', '');
            }

            if (isset($data['min_stock_alert'])) {
                $updates['min_stock_alert'] = number_format((float) $data['min_stock_alert'], 4, '.', '');
            }

            if (array_key_exists('barcode', $data)) {
                $barcode = trim((string) $data['barcode']);
                $barcode = $barcode === '' ? null : $barcode;
                if ($barcode !== null && Product::where('barcode', $barcode)->where('id', '!=', $product->id)->exists()) {
                    throw new InvalidArgumentException("Barcode {$barcode} sudah digunakan oleh produk lain.");
                }
                $updates['barcode'] = $barcode;
            }

            if (isset($data['is_active'])) {
                $updates['is_active'] = (bool) $data['is_active'];
            }

            if (!empty($updates)) {
                $product->update($updates);
            }

            return $product->fresh(['unit', 'category', 'inventoryAccount', 'cogsAccount']);
        });
    }
}

