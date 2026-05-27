<?php

namespace App\Http\Controllers;

use App\Models\Orders;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Class OrderController
 *
 * Mengelola seluruh operasi order meliputi pembuatan order, listing,
 * detail, dan perubahan status. Digunakan oleh semua role (Admin,
 * Cashier, Kitchen, Customer) dengan otorisasi melalui OrdersPolicy.
 *
 * Fitur utama:
 * - Pembuatan order dengan validasi ketersediaan dan stok produk
 * - Penghitungan total otomatis dari harga produk × quantity
 * - Generate order number format ORD-YYYYMMDD-XXXX
 * - Pengurangan stok otomatis setelah order dibuat
 * - Validasi transisi status berdasarkan role pengguna
 *
 * @package App\Http\Controllers
 */
class OrderController extends Controller
{
    use AuthorizesRequests;

    /**
     * Mapping transisi status yang diizinkan untuk setiap role.
     *
     * Format: role => [current_status => [allowed_next_statuses]]
     *
     * @var array<string, array<string, string[]>>
     */
    private const STATUS_TRANSITIONS = [
        'kitchen' => [
            'pending'   => ['preparing'],
            'preparing' => ['ready'],
        ],
        'cashier' => [
            'ready'      => ['completed'],
            'pending'    => ['cancelled'],
            'preparing'  => ['cancelled'],
            'ready2'     => ['cancelled'], // handled via 'any → cancelled' below
            'completed'  => ['cancelled'],
        ],
        'admin' => [
            'pending'   => ['preparing', 'cancelled'],
            'preparing' => ['ready', 'cancelled'],
            'ready'     => ['completed', 'cancelled'],
            'completed' => ['cancelled'],
        ],
    ];

    /**
     * POST /api/orders — Create a new order with items.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @bodyParam  table_number       integer  required  Nomor meja. Min: 1.
     * @bodyParam  notes              string   nullable  Catatan untuk order. Max: 500 karakter.
     * @bodyParam  items              array    required  Daftar item yang dipesan. Min: 1 item.
     * @bodyParam  items.*.product_id integer  required  ID produk.
     * @bodyParam  items.*.quantity   integer  required  Jumlah pesanan. Min: 1.
     * @bodyParam  items.*.notes      string   nullable  Catatan per item.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('create', Orders::class);

        $request->validate([
            'table_number'      => 'required|integer|min:1',
            'notes'             => 'nullable|string|max:500',
            'items'             => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'  => 'required|integer|min:1',
            'items.*.notes'    => 'nullable|string|max:255',
        ]);

        // Collect unique product IDs and fetch them
        $itemsInput = $request->input('items');
        $productIds = collect($itemsInput)->pluck('product_id')->unique();
        $products = Products::whereIn('id', $productIds)->get()->keyBy('id');

        // Business validation: check availability and stock
        $errors = [];
        foreach ($itemsInput as $index => $item) {
            $product = $products->get($item['product_id']);

            if (!$product || $product->status !== 'available') {
                $productName = $product ? $product->name : 'ID: ' . $item['product_id'];
                $errors["items.{$index}.product_id"][] = "Product '{$productName}' is not available.";
                continue;
            }

            if ($product->stock < $item['quantity']) {
                $errors["items.{$index}.quantity"][] = "Insufficient stock for '{$product->name}'. Available: {$product->stock}, requested: {$item['quantity']}.";
            }
        }

        if (!empty($errors)) {
            return $this->errorResponse($this->errorMessage('create', 'validation failed'), $errors, 422);
        }

        // Calculate total
        $total = 0;
        foreach ($itemsInput as $item) {
            $product = $products->get($item['product_id']);
            $total += $product->price * $item['quantity'];
        }

        // Create order + items in a transaction
        $order = DB::transaction(function () use ($request, $itemsInput, $products, $total) {
            $order = Orders::create([
                'user_id'      => $request->user()->id,
                'order_number' => Orders::generateOrderNumber(),
                'table_number' => $request->table_number,
                'status'       => 'pending',
                'total'        => $total,
                'notes'        => $request->notes,
            ]);

            foreach ($itemsInput as $item) {
                $product = $products->get($item['product_id']);

                $order->orderItems()->create([
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $product->price,
                    'notes'      => $item['notes'] ?? null,
                ]);

                // Decrement stock
                $product->decrement('stock', $item['quantity']);
            }

            return $order;
        });

        // Load items with product name for response
        $order->load(['orderItems.product:id,name']);

        $responseData = $this->formatOrderDetail($order);

        return $this->successResponse($this->successMessage('created'), $responseData, 201);
    }

    /**
     * GET /api/orders — List orders with filtering and pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Orders::class);

        $request->validate([
            'page'   => 'integer|min:1',
            'limit'  => 'integer|min:1|max:50',
            'status' => 'string|in:pending,preparing,ready,completed,cancelled',
            'date'   => 'date_format:Y-m-d',
        ]);

        $limit = $request->input('limit', 10);
        $date = $request->input('date', now()->format('Y-m-d'));

        $query = Orders::withCount('orderItems')
            ->whereDate('created_at', $date);

        // Restrict customer to see only their own orders
        if ($request->user()->role === 'customer') {
            $query->where('user_id', $request->user()->id);
        }

        // Hide unpaid pending orders from kitchen
        if ($request->user()->role === 'kitchen') {
            $query->where(function ($q) {
                $q->where('status', '!=', 'pending')
                  ->orWhereExists(function ($subquery) {
                      $subquery->select(DB::raw(1))
                               ->from('transactions')
                               ->whereColumn('transactions.order_id', 'orders.id');
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $query->orderBy('created_at', 'desc');

        $orders = $query->paginate($limit);

        if ($orders->isEmpty()) {
            return $this->successResponse($this->emptyDataMessage('order'));
        }

        // Transform data to match expected response format
        $orders->getCollection()->transform(function ($order) {
            return [
                'id'           => $order->id,
                'order_number' => $order->order_number,
                'table_number' => $order->table_number,
                'status'       => $order->status,
                'total'        => $order->total,
                'items_count'  => $order->order_items_count,
                'created_at'   => $order->created_at,
            ];
        });

        return $this->paginateResponse($this->availableDataMessage('Order'), $orders);
    }

    /**
     * GET /api/orders/{order} — Show order detail.
     *
     * @param  \App\Models\Orders  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Orders $order)
    {
        $this->authorize('view', $order);

        $order->load(['orderItems.product:id,name']);

        $responseData = $this->formatOrderDetail($order);

        return $this->successResponse($this->availableDataMessage('Order'), $responseData);
    }

    /**
     * PATCH /api/orders/{order}/status — Update order status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Orders        $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, Orders $order)
    {
        $this->authorize('updateStatus', $order);

        $request->validate([
            'status' => 'required|string|in:pending,preparing,ready,completed,cancelled',
        ]);

        $newStatus = $request->status;
        $currentStatus = $order->status;
        $userRole = $request->user()->role;

        // Validate status transition based on role
        if (!$this->isValidTransition($userRole, $currentStatus, $newStatus)) {
            return $this->errorResponse(
                $this->errorMessage('update status', "invalid transition from '{$currentStatus}' to '{$newStatus}' for role '{$userRole}'"),
                [],
                403
            );
        }

        $order->update(['status' => $newStatus]);

        return $this->successResponse($this->successMessage('updated'), [
            'id'           => $order->id,
            'order_number' => $order->order_number,
            'status'       => $order->status,
            'updated_at'   => $order->updated_at,
        ]);
    }

    /**
     * Check if a status transition is valid for the given role.
     */
    private function isValidTransition(string $role, string $currentStatus, string $newStatus): bool
    {
        // Admin can do all transitions
        if ($role === 'admin') {
            $allowed = self::STATUS_TRANSITIONS['admin'][$currentStatus] ?? [];
            return in_array($newStatus, $allowed);
        }

        // Cashier: specific transitions + any → cancelled
        if ($role === 'cashier') {
            if ($newStatus === 'cancelled' && $currentStatus !== 'cancelled') {
                return true;
            }
            $allowed = self::STATUS_TRANSITIONS['cashier'][$currentStatus] ?? [];
            return in_array($newStatus, $allowed);
        }

        // Kitchen: specific transitions only
        if ($role === 'kitchen') {
            $allowed = self::STATUS_TRANSITIONS['kitchen'][$currentStatus] ?? [];
            return in_array($newStatus, $allowed);
        }

        return false;
    }

    /**
     * Format order detail for response (used by store and show).
     */
    private function formatOrderDetail(Orders $order): array
    {
        return [
            'id'           => $order->id,
            'order_number' => $order->order_number,
            'table_number' => $order->table_number,
            'status'       => $order->status,
            'notes'        => $order->notes,
            'items'        => $order->orderItems->map(function ($item) {
                return [
                    'id'         => $item->id,
                    'product_id' => $item->product_id,
                    'name'       => $item->product->name ?? null,
                    'quantity'   => $item->quantity,
                    'price'      => $item->price,
                    'notes'      => $item->notes,
                ];
            }),
            'total'      => $order->total,
            'created_at' => $order->created_at,
        ];
    }
}
