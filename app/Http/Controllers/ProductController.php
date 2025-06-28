<?php

namespace App\Http\Controllers;

use App\Services\ProductProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ProductController extends Controller
{
    public function fetchProducts(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 50);
        $data = (new ProductProvider())->fetchProducts($offset);

        return response()->json([
            'success' => !empty($data['result']['items']),
            'data' => $data['result']['items'] ?? []
        ]);
    }

    public function downloadCsv()
    {
        try {
            $provider = new ProductProvider();
            $data = $provider->fetchProducts(0, 1000);

            if (!isset($data['result']['items']) || !is_array($data['result']['items'])) {
                return back()->with('error', 'Нет данных для экспорта.');
            }

            $products = $data['result']['items'];

            // Генерация CSV
            $csvFileName = "ozon_products_export.csv";
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $csvFileName . '"'
            ];

            $callback = function () use ($products) {
                $file = fopen('php://output', 'w');
                fwrite($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
                // Заголовки CSV
                fputcsv($file, [
                    'ID',
                    'Название',
                    'Артикул',
                    'Цена',
                    'Статус',
                    'FBO остатки',
                    'FBS остатки',
                    'Уценённый товар',
                    'Кванты'
                ]);

                foreach ($products as $product) {
                    $quants = '';
                    if (!empty($product['quants'])) {
                        $quants = implode("; ", array_map(function ($q) {
                            return "Код: {$q['quant_code']}, Размер: {$q['quant_size']}";
                        }, $product['quants']));
                    }

                    fputcsv($file, [
                        $product['product_id'] ?? '',
                        $product['name'] ?? 'Без названия',
                        $product['offer_id'] ?? '',
                        $product['price'] ?? '',
                        $product['archived'] ? 'В архиве' : 'Активен',
                        $product['has_fbo_stocks'] ? 'Есть остатки' : 'Нет остатков',
                        $product['has_fbs_stocks'] ? 'Есть остатки' : 'Нет остатков',
                        $product['is_discounted'] ? 'Уценённый товар' : 'Обычный товар',
                        $quants ?: 'Квантов нет'
                    ]);
                }

                fclose($file);
            };

            return Response::stream($callback, 200, $headers);

        } catch (\Exception $e) {
            return back()->with('error', 'Ошибка при экспорте: ' . $e->getMessage());
        }
    }
}