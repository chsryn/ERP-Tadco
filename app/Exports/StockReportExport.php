<?php

namespace App\Exports;

use App\Models\StockBalance;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        $search = $this->filters['search'] ?? null;
        $lowStockOnly = $this->filters['low_stock_only'] ?? null;

        $query = StockBalance::query()
            ->with(['product']);

        if ($search) {
            $query->whereHas('product', function ($productQuery) use ($search) {
                $productQuery->where('product_code', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('segment', 'like', "%{$search}%");
            });
        }

        if ($lowStockOnly) {
            $query->where('qty_available', '<=', 10);
        }

        return $query
            ->orderBy('qty_available')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Kode Produk',
            'Nama Produk',
            'Segment',
            'Qty Available',
            'Qty Reserved',
            'Qty Bisa Dipakai',
        ];
    }

    public function map($stock): array
    {
        $usableStock = (float) $stock->qty_available - (float) $stock->qty_reserved;

        return [
            $stock->product->product_code ?? '-',
            $stock->product->product_name ?? '-',
            $stock->product->segment ?? '-',
            (float) $stock->qty_available,
            (float) $stock->qty_reserved,
            $usableStock,
        ];
    }
}
