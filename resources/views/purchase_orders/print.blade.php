<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirm Order {{ $purchaseOrder->po_number }} - TADCO</title>
    <style>
        @page { size: A4; margin: 12mm; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 16px;
            background: #eef1f5;
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1f2937;
        }

        .toolbar {
            max-width: 210mm;
            margin: 0 auto 12px;
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 999px;
            border: 1px solid #1B84FF;
            background: #1B84FF;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-outline { background: #fff; color: #1B84FF; }

        .sheet {
            max-width: 210mm;
            margin: 0 auto;
            background: #fff;
            padding: 14mm 12mm;
            box-shadow: 0 4px 16px rgba(15, 23, 42, .12);
        }

        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .company { display: flex; align-items: center; gap: 10px; }
        .company img { height: 44px; width: auto; }
        .company-name { font-size: 15px; font-weight: 700; letter-spacing: .4px; }
        .company-sub { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; }

        .doc-title { text-align: right; }
        .doc-title h1 { margin: 0; font-size: 20px; letter-spacing: 2px; }
        .doc-title .po-number { font-size: 13px; font-weight: 700; color: #1B84FF; }

        .meta { display: flex; gap: 24px; flex-wrap: wrap; margin-bottom: 14px; }
        .meta table { border-collapse: collapse; }
        .meta th { text-align: left; font-weight: 600; color: #6b7280; padding: 1px 8px 1px 0; }
        .meta td { padding: 1px 0; font-weight: 600; }

        h2 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 14px 0 6px;
            padding-bottom: 3px;
            border-bottom: 1px solid #d1d5db;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        table.data th, table.data td {
            border: 1px solid #d1d5db;
            padding: 4px 5px;
            vertical-align: top;
        }

        table.data thead th {
            background: #f3f4f6;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .nowrap { white-space: nowrap; }
        .muted { color: #6b7280; }

        .totals { width: 62mm; margin-left: auto; border-collapse: collapse; }
        .totals th, .totals td { padding: 3px 5px; }
        .totals th { text-align: left; font-weight: 600; color: #4b5563; }
        .totals td { text-align: right; white-space: nowrap; }
        .totals tr.grand th, .totals tr.grand td {
            border-top: 2px solid #1f2937;
            font-size: 12px;
            font-weight: 700;
            color: #111827;
        }

        .notes { margin-top: 12px; font-size: 10px; }
        .notes-label { font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6b7280; }

        .doc-footer {
            margin-top: 18px;
            padding-top: 6px;
            border-top: 1px solid #d1d5db;
            font-size: 9px;
            color: #9ca3af;
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
            .sheet { box-shadow: none; max-width: none; padding: 0; }
            table.data thead { display: table-header-group; }
            table.data tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="toolbar no-print">
    <a href="{{ route('purchase_orders.show', $purchaseOrder) }}" class="btn btn-outline">&larr; Kembali</a>
    <button type="button" class="btn" onclick="window.print()">Print</button>
</div>

<div class="sheet">
    <div class="doc-header">
        <div class="company">
            <img src="{{ asset('images/Tadco-TP2.png') }}" alt="TADCO">
            <div>
                <div class="company-name">PT TADCO TRANSPORT PERKASA</div>
                <div class="company-sub">Purchase Order Confirmation</div>
            </div>
        </div>
        <div class="doc-title">
            <h1>CONFIRM ORDER</h1>
            <div class="po-number">{{ $purchaseOrder->po_number }}</div>
        </div>
    </div>

    <div class="meta">
        <table>
            <tr><th>Tanggal PO</th><td>{{ $purchaseOrder->po_date?->format('d/m/Y') ?? '-' }}</td></tr>
            <tr><th>Period</th><td>{{ $purchaseOrder->period }}</td></tr>
            <tr><th>Status</th><td>{{ strtoupper($purchaseOrder->status) }}</td></tr>
        </table>
        <table>
            <tr><th>Supplier</th><td>{{ $purchaseOrder->supplier->name ?? '-' }} ({{ $purchaseOrder->supplier->code ?? '-' }})</td></tr>
            <tr><th>Region / Port</th><td>{{ $purchaseOrder->supplier->region ?? '-' }} / {{ $purchaseOrder->supplier->port_depo ?? '-' }}</td></tr>
            <tr><th>Delivery Mode</th><td>{{ $purchaseOrder->supplier->delivery_mode ?? '-' }}</td></tr>
            <tr><th>TOP</th><td>{{ $purchaseOrder->supplier->term_of_payment_days ?? 0 }} hari</td></tr>
            <tr><th>PIC</th><td>{{ $purchaseOrder->supplier->pic_name ?? '-' }}</td></tr>
        </table>
    </div>

    <h2>Item</h2>
    <table class="data">
        <thead>
            <tr>
                <th class="text-center" style="width: 26px;">No</th>
                <th>Dynamics Code</th>
                <th>Product</th>
                <th class="text-end" style="width: 60px;">Weight (KG)</th>
                <th class="text-end" style="width: 55px;">Qty</th>
                <th class="text-center" style="width: 42px;">UOM</th>
                <th class="text-end">Price</th>
                <th class="text-end">Discount</th>
                <th class="text-end">Nett Price</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchaseOrder->items as $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $item->dynamics_code ?? '-' }}</td>
                    <td>{{ $item->product->product_name ?? '-' }}</td>
                    <td class="text-end nowrap">{{ number_format((float) $item->weight_kg, 2, ',', '.') }}</td>
                    <td class="text-end nowrap">{{ number_format((float) $item->qty_plan, 2, ',', '.') }}</td>
                    <td class="text-center">{{ $item->product->uom ?? '-' }}</td>
                    <td class="text-end nowrap">{{ number_format((float) $item->price, 0, ',', '.') }}</td>
                    <td class="text-end nowrap">{{ number_format((float) $item->special_discount, 0, ',', '.') }}</td>
                    <td class="text-end nowrap">{{ number_format((float) $item->price_nett, 0, ',', '.') }}</td>
                    <td class="text-end nowrap">{{ number_format((float) $item->total_price, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center muted">Purchase Order belum memiliki item.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Alokasi Mingguan</h2>
    <table class="data">
        <thead>
            <tr>
                <th class="text-center" style="width: 26px;">No</th>
                <th>Product</th>
                <th class="text-end">W1</th>
                <th class="text-end">W2</th>
                <th class="text-end">W3</th>
                <th class="text-end">W4</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchaseOrder->items as $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $item->product->product_name ?? '-' }}</td>
                    <td class="text-end nowrap">{{ number_format((float) $item->week_1, 2, ',', '.') }}</td>
                    <td class="text-end nowrap">{{ number_format((float) $item->week_2, 2, ',', '.') }}</td>
                    <td class="text-end nowrap">{{ number_format((float) $item->week_3, 2, ',', '.') }}</td>
                    <td class="text-end nowrap">{{ number_format((float) $item->week_4, 2, ',', '.') }}</td>
                    <td class="text-end nowrap">{{ number_format((float) $item->qty_plan, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center muted">-</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <th>Subtotal</th>
            <td>Rp {{ number_format((float) $purchaseOrder->subtotal, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Special Discount</th>
            <td>Rp {{ number_format((float) $purchaseOrder->special_discount_total, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>PPN ({{ rtrim(rtrim(number_format((float) $purchaseOrder->ppn_rate, 2, ',', '.'), '0'), ',') }}%)</th>
            <td>Rp {{ number_format((float) $purchaseOrder->ppn_amount, 0, ',', '.') }}</td>
        </tr>
        <tr class="grand">
            <th>Grand Total</th>
            <td>Rp {{ number_format((float) $purchaseOrder->grand_total, 0, ',', '.') }}</td>
        </tr>
    </table>

    @if(filled($purchaseOrder->notes))
        <div class="notes">
            <div class="notes-label">Catatan</div>
            <div>{{ $purchaseOrder->notes }}</div>
        </div>
    @endif

    <div class="doc-footer">
        <span>Dicetak {{ now()->format('d/m/Y H:i') }}</span>
        <span>{{ $purchaseOrder->po_number }} · {{ $purchaseOrder->period }}</span>
    </div>
</div>

</body>
</html>
