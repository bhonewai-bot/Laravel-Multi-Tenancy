<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Laravel Multi-Tenancy' }}</title>
    <style>
        :root {
            --bg: #0b1020;
            --panel: #111a2e;
            --panel-2: #1a243b;
            --text: #e6edf7;
            --muted: #9fb0cc;
            --border: #273657;
            --primary: #4f7cff;
            --primary-hover: #3c67e8;
            --success-bg: #10311e;
            --success-text: #9fe7ba;
            --error-bg: #34151b;
            --error-text: #f2a7b5;
            --warning-bg: #3b2e12;
            --warning-text: #f5d9a2;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: radial-gradient(circle at top right, #132344 0%, var(--bg) 40%);
            color: var(--text);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .container {
            width: min(1100px, 92%);
            margin: 32px auto;
        }

        .card {
            background: linear-gradient(180deg, var(--panel), #0f182b);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 18px;
        }

        h1 {
            margin: 0 0 16px;
            font-size: 30px;
            font-weight: 700;
        }

        h2 {
            margin: 0 0 12px;
            font-size: 20px;
        }

        p { margin: 0 0 12px; color: var(--muted); }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px;
        }

        .btn, button {
            border: 0;
            border-radius: 10px;
            padding: 10px 14px;
            background: var(--primary);
            color: #fff;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            font-size: 14px;
        }

        .btn:hover, button:hover {
            background: var(--primary-hover);
        }

        .btn.secondary {
            background: #2a3a5f;
        }

        .btn.danger, .danger {
            background: #923648;
        }

        .btn.danger:hover, .danger:hover {
            background: #7e2d3d;
        }

        .alert {
            border-radius: 10px;
            border: 1px solid transparent;
            padding: 12px 14px;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .alert.success {
            background: var(--success-bg);
            color: var(--success-text);
            border-color: #1c5f3a;
        }

        .alert.error {
            background: var(--error-bg);
            color: var(--error-text);
            border-color: #783545;
        }

        .alert.warning {
            background: var(--warning-bg);
            color: var(--warning-text);
            border-color: #7f6432;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--panel-2);
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }

        th {
            color: #c9d7f1;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: #1f2b46;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.green {
            background: #1c5f3a;
            color: #caf7dd;
        }

        .badge.yellow {
            background: #6a5121;
            color: #ffe8b2;
        }

        .badge.red {
            background: #7f2d3d;
            color: #ffd4de;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            color: #c8d5ed;
            font-size: 14px;
            font-weight: 600;
        }

        input, textarea, select {
            width: 100%;
            background: #0d1628;
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 14px;
        }

        .row-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            gap: 10px;
            color: var(--muted);
            font-size: 14px;
        }

        .pagination-links {
            display: flex;
            gap: 8px;
        }

        @media (max-width: 768px) {
            .container {
                width: 95%;
                margin: 18px auto;
            }

            h1 { font-size: 24px; }

            .form-grid { grid-template-columns: 1fr; }

            table, thead, tbody, th, td, tr {
                display: block;
            }

            thead {
                display: none;
            }

            td {
                border-bottom: 1px solid var(--border);
                padding: 10px 12px;
            }
        }
    </style>
</head>
<body>
    <main class="container">
        @yield('content')
    </main>
</body>
</html>
