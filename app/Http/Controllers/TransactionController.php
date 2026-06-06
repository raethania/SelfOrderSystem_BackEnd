<?php

namespace App\Http\Controllers;

use App\Models\Orders;
use App\Models\Transactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Class TransactionController
 *
 * Mengelola proses transaksi (pembayaran) untuk sebuah pesanan.
 *
 * @package App\Http\Controllers
 */
class TransactionController extends Controller
{
    use AuthorizesRequests;

    /**
     * GET /api/transactions
     * List transaksi dengan pagination dan filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Otorisasi: pastikan user memiliki hak akses (admin, cashier)
        $this->authorize('viewAny', Transactions::class);

        // Validasi parameter query
        $request->validate([
            'page'           => 'integer|min:1',
            'limit'          => 'integer|min:1|max:50',
            'start_date'     => 'date_format:Y-m-d',
            'end_date'       => 'date_format:Y-m-d',
            'payment_method' => 'string|in:cash,card,qris',
        ]);

        $limit = $request->input('limit', 10);

        // Load relasi order dan processor
        $query = Transactions::with(['order:id,order_number,total', 'processor:id,name']);

        // Filter rentang waktu
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        // Filter metode pembayaran
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $query->orderBy('created_at', 'desc');

        $transactions = $query->paginate($limit);

        if ($transactions->isEmpty()) {
            return $this->successResponse($this->emptyDataMessage('transaction'));
        }

        // Transform response agar sesuai dengan format yang diminta
        $transactions->getCollection()->transform(function ($trx) {
            return [
                'id'             => $trx->id,
                'order_number'   => $trx->order->order_number ?? null,
                'payment_method' => $trx->payment_method,
                'total'          => floatval($trx->order->total ?? 0),
                'amount_paid'    => floatval($trx->amount_paid),
                'change'         => floatval($trx->change),
                'processed_by'   => $trx->processor->name ?? null,
                'is_paid'        => \App\Models\Transactions::isOrderPaid($trx->order_id),
                'created_at'     => $trx->created_at,
            ];
        });

        return $this->paginateResponse($this->availableDataMessage('Transactions'), $transactions);
    }

    /**
     * POST /api/transactions
     * Membuat pembayaran untuk order tertentu.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Otorisasi: pastikan user memiliki hak akses create (admin, cashier)
        $this->authorize('create', Transactions::class);

        // Validasi input body
        $request->validate([
            'order_id'       => 'required|integer|exists:orders,id',
            'payment_method' => 'required|string|in:cash,card,qris',
            'amount_paid'    => 'required|numeric|min:0',
        ]);

        $order = Orders::find($request->order_id);

        // Validasi Bisnis: Order harus berstatus "pending"
        if ($order->status !== 'pending') {
            return $this->errorResponse(
                $this->errorMessage('create transaction', 'Order must be in "pending" status to be paid.'),
                [],
                400
            );
        }

        // Validasi Bisnis: Uang yang dibayarkan tidak boleh kurang dari total order
        if ($request->amount_paid < $order->total) {
            return $this->errorResponse(
                $this->errorMessage('create transaction', "Amount paid is insufficient. Minimum required: {$order->total}."),
                [],
                422
            );
        }

        // Gunakan DB transaction agar sinkron dengan perubahan status order
        $transaction = DB::transaction(function () use ($request, $order) {
            // Hitung kembalian
            $change = $request->amount_paid - $order->total;

            // Buat record transaksi
            $trx = Transactions::create([
                'order_id'       => $order->id,
                'payment_method' => $request->payment_method,
                'amount_paid'    => $request->amount_paid,
                'change'         => $change,
                'processed_by'   => $request->user()->id,
            ]);

            return $trx;
        });

        // Load relasi order dan user(processor) untuk response
        $transaction->load(['order:id,order_number,total', 'processor:id,name']);

        // Sesuaikan response format
        $responseData = [
            'id'             => $transaction->id,
            'order_id'       => $transaction->order_id,
            'order_number'   => $transaction->order->order_number ?? null,
            'payment_method' => $transaction->payment_method,
            'amount_paid'    => floatval($transaction->amount_paid),
            'change'         => floatval($transaction->change),
            'total'          => floatval($transaction->order->total ?? 0),
            'processed_by'   => [
                'id'   => $transaction->processor->id ?? null,
                'name' => $transaction->processor->name ?? null,
            ],
            'is_paid'        => \App\Models\Transactions::isOrderPaid($transaction->order_id),
            'created_at'     => $transaction->created_at,
        ];

        return $this->successResponse($this->successMessage('created'), $responseData, 201);
    }
}
