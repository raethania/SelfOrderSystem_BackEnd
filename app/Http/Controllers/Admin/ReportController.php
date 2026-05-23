<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Orders;
use App\Models\Products;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Class ReportController
 *
 * Mengelola laporan penjualan, produk terlaris, dan stok rendah.
 * Akses dibatasi menggunakan Gate 'view-reports'.
 *
 * @package App\Http\Controllers\Admin
 */
class ReportController extends Controller
{
    /**
     * GET /api/reports/sales
     * Laporan agregasi penjualan harian/mingguan/bulanan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sales(Request $request)
    {
        // Otorisasi: Pastikan admin
        Gate::authorize('view-reports');

        $request->validate([
            'start_date' => 'date_format:Y-m-d',
            'end_date'   => 'date_format:Y-m-d',
            'group_by'   => 'in:daily,weekly,monthly',
        ]);

        $startDate = $request->input('start_date', Carbon::now()->subDays(7)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $groupBy = $request->input('group_by', 'daily');

        // Setup format tanggal untuk fungsi MySQL DATE_FORMAT()
        $dateFormat = '%Y-%m-%d'; // daily
        if ($groupBy === 'monthly') {
            $dateFormat = '%Y-%m';
        } elseif ($groupBy === 'weekly') {
            $dateFormat = '%Y-%u'; // Year-Week format
        }

        // 1. Kalkulasi angka summary (berdasarkan status 'completed')
        $summaryData = Orders::where('status', 'completed')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('SUM(total) as total_revenue, COUNT(id) as total_orders')
            ->first();

        $totalRevenue = (float) ($summaryData->total_revenue ?? 0);
        $totalOrders = (int) ($summaryData->total_orders ?? 0);
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders) : 0;

        // 2. Fetch data breakdown berdasarkan parameter grouping date
        $breakdownData = Orders::where('status', 'completed')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as date, SUM(total) as revenue, COUNT(id) as orders")
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        if ($breakdownData->isEmpty()) {
            return $this->successResponse($this->emptyDataMessage('sales report'));
        }

        $responseData = [
            'summary' => [
                'total_revenue'   => $totalRevenue,
                'total_orders'    => $totalOrders,
                'avg_order_value' => $avgOrderValue,
            ],
            'breakdown' => $breakdownData->map(function ($item) {
                return [
                    'date'    => $item->date,
                    'revenue' => (float) $item->revenue,
                    'orders'  => (int) $item->orders,
                ];
            })
        ];

        return $this->successResponse($this->availableDataMessage('sales report'), $responseData);
    }

    /**
     * GET /api/reports/top-products
     * Laporan produk terlaris.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function topProducts(Request $request)
    {
        Gate::authorize('view-reports');

        $request->validate([
            'start_date' => 'date_format:Y-m-d',
            'end_date'   => 'date_format:Y-m-d',
            'limit'      => 'integer|min:1|max:100',
        ]);

        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d')); // default 30 hari untuk top produk
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $limit = $request->input('limit', 10);

        // Agregasi tabel products + order_items + orders
        $topProducts = DB::table('products')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('products.id as product_id, products.name, SUM(order_items.quantity) as total_sold, SUM(order_items.quantity * order_items.price) as revenue')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->paginate($limit);

        if ($topProducts->isEmpty()) {
            return $this->successResponse($this->emptyDataMessage('top products report'));
        }

        $topProducts->getCollection()->transform(function ($item) {
            return [
                'product_id' => (int) $item->product_id,
                'name'       => $item->name,
                'total_sold' => (int) $item->total_sold,
                'revenue'    => (float) $item->revenue,
            ];
        });

        return $this->paginateResponse($this->availableDataMessage('top products report'), $topProducts);
    }

    /**
     * GET /api/reports/low-stock
     * Laporan stok rendah (di bawah threshold).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lowStock(Request $request)
    {
        Gate::authorize('view-reports');

        $request->validate([
            'threshold' => 'integer|min:0',
            'limit'     => 'integer|min:1',
        ]);

        $threshold = $request->input('threshold', 10);
        $limit = $request->input('limit', 10);

        $lowStockProducts = Products::where('stock', '<=', $threshold)
            ->select('id as product_id', 'name', 'stock', 'status')
            ->orderBy('stock', 'asc')
            ->paginate($limit);

        if ($lowStockProducts->isEmpty()) {
            return $this->successResponse($this->emptyDataMessage('low stock report'));
        }

        $lowStockProducts->getCollection()->transform(function ($product) {
            return [
                'product_id' => $product->product_id,
                'name'       => $product->name,
                'stock'      => $product->stock,
                'status'     => $product->status,
            ];
        });

        return $this->paginateResponse($this->availableDataMessage('low stock report'), $lowStockProducts);
    }

    /**
     * GET /api/reports/sales/export
     * Export Laporan penjualan ke PDF.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function exportSales(Request $request)
    {
        Gate::authorize('view-reports');

        $request->validate([
            'start_date' => 'date_format:Y-m-d',
            'end_date'   => 'date_format:Y-m-d',
            'group_by'   => 'in:daily,weekly,monthly',
        ]);

        $startDate = $request->input('start_date', Carbon::now()->subDays(7)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $groupBy = $request->input('group_by', 'daily');

        $dateFormat = '%Y-%m-%d';
        if ($groupBy === 'monthly') {
            $dateFormat = '%Y-%m';
        } elseif ($groupBy === 'weekly') {
            $dateFormat = '%Y-%u';
        }

        $summaryData = Orders::where('status', 'completed')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('SUM(total) as total_revenue, COUNT(id) as total_orders')
            ->first();

        $totalRevenue = (float) ($summaryData->total_revenue ?? 0);
        $totalOrders = (int) ($summaryData->total_orders ?? 0);
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders) : 0;

        $breakdownData = Orders::where('status', 'completed')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as date, SUM(total) as revenue, COUNT(id) as orders")
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(500)
            ->get();

        $pdf = Pdf::loadView('reports.sales', [
            'summary' => [
                'total_revenue'   => $totalRevenue,
                'total_orders'    => $totalOrders,
                'avg_order_value' => $avgOrderValue,
            ],
            'breakdown' => $breakdownData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'groupBy' => $groupBy,
        ]);

        return $pdf->download('sales-report-' . Carbon::now()->format('Y-md-His') . '.pdf');
    }

    /**
     * GET /api/reports/top-products/export
     * Export Laporan produk terlaris ke PDF.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function exportTopProducts(Request $request)
    {
        Gate::authorize('view-reports');

        $request->validate([
            'start_date' => 'date_format:Y-m-d',
            'end_date'   => 'date_format:Y-m-d',
        ]);

        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        $topProducts = DB::table('products')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('products.id as product_id, products.name, SUM(order_items.quantity) as total_sold, SUM(order_items.quantity * order_items.price) as revenue')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(500)
            ->get();

        $pdf = Pdf::loadView('reports.top-products', [
            'products' => $topProducts,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        return $pdf->download('top-products-report-' . Carbon::now()->format('Y-md-His') . '.pdf');
    }

    /**
     * GET /api/reports/low-stock/export
     * Export Laporan stok rendah ke PDF.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function exportLowStock(Request $request)
    {
        Gate::authorize('view-reports');

        $request->validate([
            'threshold' => 'integer|min:0',
        ]);

        $threshold = $request->input('threshold', 10);

        $lowStockProducts = Products::where('stock', '<=', $threshold)
            ->select('id as product_id', 'name', 'stock', 'status')
            ->orderBy('stock', 'asc')
            ->limit(500)
            ->get();

        $pdf = Pdf::loadView('reports.low-stock', [
            'products' => $lowStockProducts,
            'threshold' => $threshold,
        ]);

        return $pdf->download('low-stock-report-' . Carbon::now()->format('Y-md-His') . '.pdf');
    }
}
