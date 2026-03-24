<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>{{ $title ?? 'Products' }} - {{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <style>
        :root {
            --page-bg: #f5f7fb;
            --surface: #ffffff;
            --border: #e3e8ef;
            --text: #111827;
            --muted: #6b7280;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --danger: #dc2626;
            --danger-hover: #b91c1c;
            --success-bg: #ecfdf3;
            --success-text: #166534;
            --error-bg: #fef2f2;
            --error-text: #991b1b;
            --shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Figtree', sans-serif;
            background: var(--page-bg);
            color: var(--text);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        img {
            display: block;
            max-width: 100%;
        }

        .page {
            min-height: 100vh;
            padding: 32px 20px 48px;
        }

        .container {
            max-width: 1120px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
        }

        .page-title {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 700;
        }

        .page-subtitle {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: var(--shadow);
        }

        .card-body {
            padding: 24px;
        }

        .toolbar,
        .actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn,
        .btn-secondary,
        .btn-danger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px solid transparent;
            font-size: 0.92rem;
            font-weight: 600;
            cursor: pointer;
            transition: 160ms ease;
            background: transparent;
        }

        .btn {
            background: var(--primary);
            color: #fff;
        }

        .btn:hover {
            background: var(--primary-hover);
        }

        .btn-secondary {
            background: #fff;
            color: var(--text);
            border-color: var(--border);
        }

        .btn-secondary:hover {
            background: #f9fafb;
        }

        .btn-danger {
            background: #fff;
            color: var(--danger);
            border-color: #fecaca;
        }

        .btn-danger:hover {
            background: #fef2f2;
            color: var(--danger-hover);
        }

        .alert {
            margin-bottom: 16px;
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 0.92rem;
        }

        .alert-success {
            background: var(--success-bg);
            color: var(--success-text);
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: var(--error-bg);
            color: var(--error-text);
            border: 1px solid #fecaca;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
        }

        .table th,
        .table td {
            padding: 16px 18px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }

        .table th {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
            background: #f9fafb;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .thumb {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #f9fafb;
        }

        .thumb-placeholder {
            width: 56px;
            height: 56px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            border: 1px dashed var(--border);
            color: var(--muted);
            background: #f9fafb;
            font-size: 0.75rem;
        }

        .item-title {
            margin: 0 0 6px;
            font-size: 0.98rem;
            font-weight: 600;
        }

        .item-text {
            margin: 0;
            color: var(--muted);
            line-height: 1.5;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .field,
        .field-full {
            display: grid;
            gap: 8px;
        }

        .field-full {
            grid-column: 1 / -1;
        }

        .label {
            font-size: 0.92rem;
            font-weight: 600;
        }

        .input,
        .textarea,
        .file-input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #fff;
            color: var(--text);
            font: inherit;
        }

        .input:focus,
        .textarea:focus,
        .file-input:focus {
            outline: none;
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .textarea {
            min-height: 140px;
            resize: vertical;
        }

        .help {
            color: var(--muted);
            font-size: 0.84rem;
        }

        .error {
            color: var(--danger);
            font-size: 0.84rem;
        }

        .section-title {
            margin: 0 0 18px;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 24px;
        }

        .preview-box {
            width: 100%;
            aspect-ratio: 1 / 1;
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            background: #f9fafb;
            display: grid;
            place-items: center;
        }

        .preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .detail-list {
            display: grid;
            gap: 14px;
        }

        .detail-item {
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff;
        }

        .detail-label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
        }

        .detail-value {
            margin-top: 6px;
            font-size: 0.98rem;
            font-weight: 600;
        }

        .pagination {
            margin-top: 18px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .stat-card {
            padding: 22px;
            border-radius: 16px;
            border: 1px solid var(--border);
            background: #fff;
            box-shadow: var(--shadow);
        }

        .stat-card--primary {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border-color: transparent;
            color: #fff;
        }

        .stat-card__label {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
        }

        .stat-card--primary .stat-card__label {
            color: rgba(255, 255, 255, 0.78);
        }

        .stat-card__value {
            margin-top: 14px;
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
        }

        .catalog-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            padding: 22px 24px;
            border-bottom: 1px solid var(--border);
        }

        .catalog-title {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 700;
        }

        .catalog-subtitle {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 0.92rem;
        }

        .sku-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: #eef2f7;
            color: #4b5563;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .table th.center,
        .table td.center {
            text-align: center;
            vertical-align: middle;
        }

        .form-shell {
            display: grid;
            gap: 24px;
        }

        .form-section {
            border: 1px solid var(--border);
            border-radius: 16px;
            background: #fff;
            box-shadow: var(--shadow);
        }

        .form-section__header {
            padding: 20px 24px 0;
        }

        .form-section__eyebrow {
            margin: 0 0 8px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .form-section__title {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 700;
        }

        .form-section__copy {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .form-section__body {
            padding: 24px;
        }

        .upload-panel {
            display: grid;
            gap: 12px;
        }

        .upload-dropzone {
            display: grid;
            place-items: center;
            min-height: 220px;
            border: 2px dashed #d7deea;
            border-radius: 16px;
            background: #f8fafc;
            text-align: center;
            padding: 24px;
            cursor: pointer;
        }

        .upload-dropzone__icon {
            width: 56px;
            height: 56px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            margin: 0 auto 14px;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            background: #ffffff;
            border: 1px solid #dbe5f2;
        }

        .upload-dropzone__title {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
        }

        .upload-dropzone__hint {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 0.88rem;
        }

        .form-actions-bar {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 24px;
            border-top: 1px solid var(--border);
            background: #fbfcfe;
            border-bottom-left-radius: 16px;
            border-bottom-right-radius: 16px;
        }

        @media (max-width: 900px) {
            .page-header,
            .detail-grid,
            .form-grid {
                grid-template-columns: 1fr;
                flex-direction: column;
                align-items: flex-start;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .catalog-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .form-actions-bar {
                flex-direction: column-reverse;
            }

            .card-body {
                padding: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="container">
            <div class="page-header">
                <div>
                    <h1 class="page-title">{{ $title ?? 'Products' }}</h1>
                    @isset($subtitle)
                        <p class="page-subtitle">{{ $subtitle }}</p>
                    @endisset
                </div>

                <div class="toolbar">
                    <a href="{{ route('product.index') }}" class="btn-secondary">Products</a>
                    <a href="{{ route('dashboard', absolute: false) }}" class="btn-secondary">Dashboard</a>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            {{ $slot }}
        </div>
    </div>
</body>
</html>
