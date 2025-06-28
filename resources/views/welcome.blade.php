<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
    </style>
    <title>Laravel</title>
</head>
<body class="min-h-screen p-6 bg-gray-50">

<!-- Заголовок -->
<div class="max-w-7xl mx-auto mb-8 text-center">
    <h1 class="text-3xl font-bold text-gray-800">Товары с OZON</h1>
    <p class="mt-2 text-sm text-gray-500">Нажмите «Загрузить», чтобы получить список товаров</p>
</div>

<!-- Кнопка "Загрузить" -->
<div class="max-w-7xl mx-auto mb-6 text-right">
    <button id="loadBtn"
            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow transition duration-300">
        Загрузить
    </button>
</div>
<div class="max-w-7xl mx-auto mb-6 text-right">
    <a target="_blank" href="/download"
       class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded shadow transition duration-300">
        Экспорт в CSV
    </a>
</div>

<!-- Индикатор загрузки -->
<div id="loading" class="hidden flex justify-center items-center mt-8">
    <div class="w-10 h-10 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
    <span class="ml-3 text-gray-600">Загрузка...</span>
</div>

<!-- Таблица товаров -->
<div class="max-w-7xl mx-auto overflow-x-auto">
    <table id="products-table" class="hidden w-full table-auto bg-white shadow-md rounded-lg overflow-hidden">
        <thead class="bg-gray-100">
        <tr>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Название</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">ID</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Артикул</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Цена</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Статус</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">FBO</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">FBS</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Уценённый</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Кванты</th>
        </tr>
        </thead>
        <tbody id="products-body" class="divide-y divide-gray-200">
        <!-- Данные будут добавлены динамически -->
        </tbody>
    </table>
</div>

<script>
    const loadBtn = document.getElementById('loadBtn');
    const loadingIndicator = document.getElementById('loading');
    const productsBody = document.getElementById('products-body');
    const productsTable = document.getElementById('products-table');

    let offset = 0;

    async function loadProducts() {
        loadBtn.disabled = true;
        loadingIndicator.classList.remove('hidden');
        productsTable.classList.add('hidden');

        try {
            const res = await fetch(`/api/products?offset=${offset}`);
            if (!res.ok) throw new Error(`HTTP ошибка: ${res.status}`);

            const contentType = res.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                throw new Error("Ответ не является JSON");
            }

            const data = await res.json();
            loadBtn.disabled = false;
            loadingIndicator.classList.add('hidden');

            if (!data.success || !Array.isArray(data.data)) {
                console.error("Ошибка загрузки данных", data.message);
                alert("Ошибка при получении данных");
                return;
            }

            const products = data.data;

            if (products.length === 0) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 9;
                cell.textContent = "Больше товаров нет.";
                cell.className = "text-center py-4 text-gray-500";
                row.appendChild(cell);
                productsBody.appendChild(row);
                loadBtn.style.display = 'none';
                return;
            }

            products.forEach(product => {
                const row = document.createElement('tr');

                row.innerHTML = `
                    <td class="px-4 py-3 text-gray-800 truncate max-w-xs">${product.name || 'Без названия'}</td>
                    <td class="px-4 py-3 text-gray-600">${product.product_id || '—'}</td>
                    <td class="px-4 py-3 text-gray-600">${product.offer_id || '—'}</td>
                    <td class="px-4 py-3 text-blue-600 font-semibold">${product.price ? `${product.price} ₽` : 'Не указана'}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center">
                            <span class="inline-block w-3 h-3 rounded-full mr-2 ${product.archived ? 'bg-red-500' : 'bg-green-500'}"></span>
                            <span>${product.archived ? 'В архиве' : 'Активен'}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center">
                            <span class="inline-block w-3 h-3 rounded-full mr-2 ${product.has_fbo_stocks ? 'bg-green-500' : 'bg-gray-400'}"></span>
                            <span>${product.has_fbo_stocks ? 'Есть остатки' : 'Нет остатков'}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center">
                            <span class="inline-block w-3 h-3 rounded-full mr-2 ${product.has_fbs_stocks ? 'bg-green-500' : 'bg-gray-400'}"></span>
                            <span>${product.has_fbs_stocks ? 'Есть остатки' : 'Нет остатков'}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center">
                            <span class="inline-block w-3 h-3 rounded-full mr-2 ${product.is_discounted ? 'bg-yellow-500' : 'bg-gray-400'}"></span>
                            <span>${product.is_discounted ? 'Уценённый товар' : 'Обычный товар'}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        ${product.quants && product.quants.length > 0
                    ? `<ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                                ${product.quants.map(q => `
                                    <li>Код: ${q.quant_code || '—'}, Размер: ${q.quant_size || 0}</li>
                                `).join('')}
                              </ul>`
                    : '<span class="text-gray-400 italic">Квантов нет</span>'
                }
                    </td>
                `;
                productsBody.appendChild(row);
            });

            offset += products.length;
            productsTable.classList.remove('hidden');

        } catch (err) {
            loadBtn.disabled = false;
            loadingIndicator.classList.add('hidden');
            console.error("Ошибка запроса:", err);
            alert("Произошла ошибка при загрузке товаров.");
        }
    }

    // Обработчик клика по кнопке
    loadBtn.addEventListener('click', () => {
        loadProducts();
    });

</script>
</body>
</html>