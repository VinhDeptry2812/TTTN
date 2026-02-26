<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách Sản phẩm - Nội Thất Cao Cấp</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #c29d59;
            /* Màu vàng đồng sang trọng */
            --bg: #1a1a1a;
            --card: #262626;
            --text: #ffffff;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .container {
            max-width: 1000px;
            width: 100%;
        }

        .stats {
            margin-bottom: 30px;
            color: #888;
            font-size: 0.9rem;
            text-align: center;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        .card {
            background: var(--card);
            border-radius: 8px;
            padding: 0;
            border: 1px solid #333;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-10px);
            border-color: var(--primary);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .card-body {
            padding: 24px;
        }

        .category {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
        }

        .name {
            font-size: 1.4rem;
            font-weight: 600;
            margin: 0 0 12px 0;
            line-height: 1.2;
        }

        .price {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary);
        }

        .base-price {
            font-size: 0.9rem;
            text-decoration: line-through;
            color: #666;
            margin-left: 10px;
        }

        .badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--primary);
            color: #000;
            font-size: 0.65rem;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 0;
            z-index: 10;
        }

        .footer {
            margin-top: 60px;
            color: #555;
            font-size: 0.8rem;
            text-align: center;
            border-top: 1px solid #333;
            padding-top: 20px;
            width: 100%;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 style="text-align: center;">Home Furniture</h1>
        <div class="stats">Premium Collection | <span style="color: #4ade80;">DB Connected ✅</span> |
            {{ $products->count() }} items available</div>

        <div class="grid">
            @foreach($products as $product)
                <div class="card">
                    @if($product->is_featured)
                        <div class="badge">BEST SELLER</div>
                    @endif
                    <div class="card-body">
                        <span class="category">{{ $product->category->name }}</span>
                        <h2 class="name">{{ $product->name }}</h2>
                        <div class="price">
                            {{ number_format($product->sale_price ?? $product->base_price) }}đ
                            @if($product->sale_price)
                                <span class="base-price">{{ number_format($product->base_price) }}đ</span>
                            @endif
                        </div>
                        <p style="color: #888; font-size: 0.85rem; margin-top: 15px; line-height: 1.6;">
                            {{ $product->description }}
                        </p>
                        <div style="margin-top: 20px; font-size: 0.75rem; color: #555;">
                            Material: {{ $product->material }} | Brand: {{ $product->brand }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="footer">
            &copy; 2026 Home Furniture Premium Store - Powered by Laravel 12
        </div>
    </div>
</body>

</html>