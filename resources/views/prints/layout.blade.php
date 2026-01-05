<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - {{ $hospital->hospital_name ?? 'HIMS' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }

        .print-container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 10mm;
        }

        /* Header Styles */
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .hospital-name {
            font-size: 20px;
            font-weight: bold;
            color: #1a5276;
            margin-bottom: 3px;
        }

        .hospital-address {
            font-size: 11px;
            color: #555;
        }

        .hospital-contact {
            font-size: 10px;
            color: #666;
        }

        .document-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
            padding: 5px;
            background: #f0f0f0;
            border: 1px solid #ddd;
        }

        /* Patient Info Box */
        .patient-info {
            display: flex;
            flex-wrap: wrap;
            border: 1px solid #ddd;
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            width: 100%;
            border-bottom: 1px solid #eee;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-cell {
            flex: 1;
            padding: 6px 10px;
            border-right: 1px solid #eee;
        }

        .info-cell:last-child {
            border-right: none;
        }

        .info-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }

        .info-value {
            font-weight: 600;
            color: #333;
        }

        /* Section Styles */
        .section {
            margin-bottom: 15px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            background: #e8e8e8;
            padding: 5px 10px;
            border-left: 4px solid #1a5276;
            margin-bottom: 8px;
        }

        .section-content {
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-top: none;
            min-height: 30px;
        }

        /* Table Styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }

        .data-table th {
            background: #f5f5f5;
            font-weight: 600;
            font-size: 11px;
        }

        .data-table td {
            font-size: 11px;
        }

        /* Signature Section */
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 20px;
        }

        .signature-box {
            text-align: center;
            width: 200px;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-bottom: 5px;
        }

        .signature-label {
            font-size: 10px;
            color: #666;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #888;
            text-align: center;
        }

        /* Print Styles */
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .print-container {
                padding: 5mm;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: A4;
                margin: 10mm;
            }
        }

        /* Print Button */
        .print-actions {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .btn-print {
            padding: 10px 20px;
            background: #1a5276;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }

        .btn-print:hover {
            background: #154360;
        }

        .btn-close {
            padding: 10px 20px;
            background: #666;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="print-actions no-print">
        <button class="btn-print" onclick="window.print()">Print</button>
        <button class="btn-close" onclick="window.close()">Close</button>
    </div>

    <div class="print-container">
        @yield('content')
    </div>

    @yield('scripts')
</body>
</html>
