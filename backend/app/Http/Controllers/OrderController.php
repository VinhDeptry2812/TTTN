<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Services\DiscountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class OrderController extends Controller
{
    protected $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    /**
     * @OA\Post(
     *     path="/checkout",
     *     summary="Đồ uống đơn hàng (Checkout)",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"receiver_name", "receiver_phone", "shipping_address", "payment_method"},
     *             @OA\Property(property="receiver_name", type="string", example="Nguyen Van A"),
     *             @OA\Property(property="receiver_phone", type="string", example="0123456789"),
     *             @OA\Property(property="shipping_address", type="string", example="123 Duong ABC, Quan 1, TP.HCM"),
     *             @OA\Property(property="payment_method", type="string", enum={"cod", "bank_transfer", "momo", "vnpay"}, example="cod"),
     *             @OA\Property(property="coupon_code", type="string", nullable=true, example="SUMMER2024"),
     *             @OA\Property(property="note", type="string", nullable=true, example="Giao gio hanh chinh")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201, 
     *         description="Đặt hàng thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đặt hàng thành công!"),
     *             @OA\Property(property="order", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422, 
     *         description="Lỗi validation hoặc mã giảm giá không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Giỏ hàng của bạn đang trống.")
     *         )
     *     )
     * )
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'receiver_name' => 'required|string|max:100',
            'receiver_phone' => 'required|string|max:15',
            'shipping_address' => 'required|string|max:500',
            'payment_method' => 'required|in:cod,bank_transfer,momo,vnpay',
            'coupon_code' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $user = auth()->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart || $cart->items()->count() == 0) {
            return response()->json(['success' => false, 'message' => 'Giỏ hàng của bạn đang trống.'], 422);
        }

        $subtotal = $cart->items->sum(function ($item) {
            return $item->quantity * ($item->variant->sale_price ?? $item->variant->price);
        });

        $discountAmount = 0;
        $couponId = null;

        if ($request->coupon_code) {
            try {
                $coupon = $this->discountService->validateCoupon($request->coupon_code, $subtotal);
                $discountAmount = $this->discountService->calculateDiscount($coupon, $subtotal);
                $couponId = $coupon->id;
            } catch (Exception $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
        }

        $shippingFee = 30000; // Phí ship cố định (ví dụ)
        $totalAmount = $subtotal - $discountAmount + $shippingFee;

        try {
            DB::beginTransaction();

            $order = Order::create([
                'user_id' => $user->id,
                'coupon_id' => $couponId,
                'order_code' => 'ORD-' . strtoupper(Str::random(10)),
                'receiver_name' => $request->receiver_name,
                'receiver_phone' => $request->receiver_phone,
                'shipping_address' => $request->shipping_address,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_fee' => $shippingFee,
                'total_amount' => max(0, $totalAmount),
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'note' => $request->note,
            ]);

            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'variant_id' => $cartItem->product_variant_id,
                    'product_name' => $cartItem->product->name,
                    'variant_info' => $cartItem->variant->color . ' - ' . $cartItem->variant->size,
                    'price' => $cartItem->variant->sale_price ?? $cartItem->variant->price,
                    'quantity' => $cartItem->quantity,
                    'subtotal' => $cartItem->quantity * ($cartItem->variant->sale_price ?? $cartItem->variant->price),
                ]);
            }

            // Nếu có mã giảm giá, tăng used_count
            if ($couponId) {
                $this->discountService->applyCoupon($request->coupon_code);
            }

            // Xóa giỏ hàng
            $cart->items()->delete();
            $cart->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đặt hàng thành công!',
                'order' => $order
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
