<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->nomor }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Arial", sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
        }

        .invoice-container {
            width: 210mm;
            background-color: #ffffff;
            border-bottom: 2px dashed #dddddd;
            position: relative;
            padding-bottom: 20px;
        }

        .invoice-table {
            width: 100%;
            font-size: 12px;
            border-collapse: collapse;
        }

        .header-row {
            background-color: #dbeafe;
        }

        .header-logo {
            padding: 10px 20px;
            text-align: left;
        }

        .header-logo img {
            height: 48px;
            object-fit: contain;
        }

        .header-info {
            padding: 10px 20px;
            text-align: right;
            font-size: 14px;
        }

        .info-table {
            width: 100%;
        }

        .info-table td {
            padding: 1px 4px;
        }

        .info-table td:first-child {
            width: 50%;
        }

        .info-table td:last-child {
            text-align: right;
            font-weight: bold;
        }

        .billing-row {
            background-color: #ffffff;
        }

        .billing-from,
        .billing-to {
            padding: 20px;
            vertical-align: top;
        }

        .billing-to {
            text-align: right;
            width: 42%;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #9ca3af;
            padding: 8px;
        }

        .items-table th {
            background-color: #bfdbfe;
            font-size: 14px;
            text-align: left;
        }

        .items-table th:first-child {
            width: 40px;
        }

        .items-table th:last-child {
            width: 160px;
            text-align: right;
        }

        .items-table td:last-child {
            text-align: right;
        }

        .summary-row {
            padding: 20px;
            vertical-align: top;
        }

        .summary-left {
            position: relative;
        }

        .note-box {
            padding: 10px;
            border: 1px solid #9ca3af;
            margin-bottom: 20px;
        }

        .note-title {
            font-weight: bold;
        }

        .note-content {
            font-style: italic;
            opacity: 0.75;
        }

        .payment-date {
            margin-bottom: 20px;
        }

        .payment-date-highlight {
            margin-left: 8px;
            padding: 4px 40px;
            background-color: rgba(147, 197, 253, 0.7);
            display: inline-block;
        }

        .payment-info {
            margin-top: 20px;
        }

        .payment-title {
            font-weight: bold;
            margin-bottom: 4px;
        }

        .terms-section {
            margin-top: 40px;
        }

        .terms-title {
            font-weight: bold;
        }

        .terms-link {
            color: #1d4ed8;
        }

        .summary-table {
            width: 100%;
            border: 1px solid #9ca3af;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 8px;
            border-bottom: 1px solid #9ca3af;
        }

        .summary-table td:first-child {
            font-size: 14px;
        }

        .summary-table td:last-child {
            text-align: right;
        }

        .summary-total {
            font-weight: bold;
        }

        .status-badge {
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 8px;
            background-color: #60a5fa;
            margin-top: 40px;
        }

        .status-lunas {
            background-color: #16a34a;
            padding: 10px 8px;
            font-size: 18px;
        }

        .stempel img {
            margin-top: -190px;
            margin-bottom: -150px;
            margin-left: -150px;
            height: 250px;
            object-fit: contain;
            position: relative;
            z-index: 110;
            opacity: 0.9;
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <table class="invoice-table">
            <tbody>
                <!-- Header Row -->
                <tr class="header-row">
                    <td class="header-logo">
                        @if($invoice->unit === 'vcm')
                        <img src="{{ public_path('assets/images/logo-vcm.webp') }}" alt="Logo VCM" />
                        @else
                        <img src="{{ public_path('assets/images/logo-vdi.webp') }}" alt="Logo VDI" />
                        @endif
                    </td>
                    <td class="header-info">
                        <table class="info-table">
                            <tbody>
                                <tr>
                                    <td>Nomor Invoice:</td>
                                    <td>{{ $invoice->nomor }}</td>
                                </tr>
                                <tr>
                                    <td>Jatuh Tempo:</td>
                                    <td>{{ $dueDate }}</td>
                                </tr>
                                <tr>
                                    <td>Tanggal Invoice:</td>
                                    <td>{{ $formattedDate }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>

                <!-- Billing Information Row -->
                <tr class="billing-row">
                    <td class="billing-from">
                        <div><strong>Tagihan kepada :</strong></div>
                        <div>{{ $invoice->customer->nama }}</div>
                        <div>{{ $invoice->customer->alamat }}</div>
                    </td>
                    <td class="billing-to">
                        <div><strong>Tagihan dari :</strong></div>
                        <div>
                            @if($invoice->unit === 'vcm')
                            Velocity Cyber Media
                            @else
                            Velocity Developer Indonesia
                            @endif
                            <br>
                            Kebonagung RT 04 / RW 01 Jarum, Bayat, <br>
                            Klaten, Jawa Tengah <br>
                            bantuanvdc@gmail.com
                        </div>
                    </td>
                </tr>

                <!-- Items Table Row -->
                <tr>
                    <td colspan="2" style="padding: 5px 20px;">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Pekerjaan</th>
                                    <th>Harga</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        @if($item->jenis){{ $item->jenis }}@endif
                                        @if($item->webhost && $item->webhost->nama_web){{ $item->webhost->nama_web }}@endif
                                        {{ $item->nama }}
                                    </td>
                                    <td>{{ number_format($item->harga, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach

                                @for($i = 0; $i < max(0, 3 - count($invoice->items)); $i++)
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                    @endfor
                            </tbody>
                        </table>
                    </td>
                </tr>

                <!-- Summary Row -->
                <tr>
                    <td class="summary-row summary-left">
                        <div class="note-box">
                            <div class="note-title">Note :</div>
                            <div class="note-content">{{ $invoice->note }}</div>
                        </div>

                        <div class="payment-date">
                            <span style="vertical-align: top;">Dibayar tanggal:</span>
                            <span class="payment-date-highlight">{{ $formattedPaymentDate }}</span>
                        </div>

                        <div class="payment-info">
                            <div class="payment-title">Pembayaran ditransfer ke :</div>
                            @if($invoice->unit === 'vcm')
                            <div>CV. Velocity Cyber Media</div>
                            <div>BCA : 0301545834</div>
                            @else
                            <div>CV. Velocity Developer Indonesia</div>
                            <div>BCA : 0301545796</div>
                            @endif
                        </div>

                        <div class="terms-section">
                            <div class="terms-title">Terms & Conditions:</div>
                            <a href="https://velocitydeveloper.com/syarat-dan-ketentuan/" class="terms-link">velocitydeveloper.com/syarat-dan-ketentuan/</a>
                        </div>
                    </td>
                    <td class="summary-row" style="position: relative;">
                        <table class="summary-table" style="position: relative;">
                            <tbody>
                                <tr>
                                    <td>Sub Total</td>
                                    <td>{{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Pajak</td>
                                    <td>{{ number_format($invoice->nominal_pajak, 0, ',', '.') }} ({{ $invoice->pajak }}%)</td>
                                </tr>
                                <tr class="summary-total">
                                    <td>TOTAL</td>
                                    <td>{{ number_format($total, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Dibayar</td>
                                    <td>{{ number_format($paidAmount, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Terhutang</td>
                                    <td>{{ number_format($dueAmount, 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="status-badge {{ $invoice->status === 'lunas' ? 'status-lunas' : '' }}">
                            {{ $invoice->status === 'lunas' ? 'LUNAS' : 'BELUM LUNAS' }}
                        </div>

                        @if($invoice->status === 'lunas')
                        <div class="stempel">
                            @if($invoice->unit === 'vcm')
                            <img src="{{ public_path('assets/images/stempel-vcm.webp') }}" alt="Stempel VCM" />
                            @else
                            <img src="{{ public_path('assets/images/stempel-vdi.webp') }}" alt="Stempel VDI" />
                            @endif
                        </div>
                        @endif

                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>