<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Http\Requests\StoreProductVariantRequest;
use App\Http\Requests\UpdateProductVariantRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;


/**
 * @OA\Schema(
 *     schema="ProductVariant",
 *     title="ProductVariant",
 *     description="Mô hình Biến thể sản phẩm",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="product_id", type="integer", example=1, description="ID của sản phẩm gốc"),
 *     @OA\Property(property="sku", type="string", example="SOFA-BRN-2.8M", description="Mã kho riêng cho biến thể"),
 *     @OA\Property(property="color", type="string", nullable=true, example="Nâu cà phê", description="Màu sắc"),
 *     @OA\Property(property="wood_type", type="string", nullable=true, example="Gỗ sồi", description="Loại gỗ"),
 *     @OA\Property(property="upholstery", type="string", nullable=true, example="Da bò thật", description="Chất liệu vải bọc/da"),
 *     @OA\Property(property="finish", type="string", nullable=true, example="Walnut", description="Kiểu hoàn thiện bề mặt"),
 *     @OA\Property(property="size", type="string", nullable=true, example="2.8m x 1.7m", description="Kích thước tổng quát"),
 *     @OA\Property(property="width_cm", type="number", nullable=true, example=280),
 *     @OA\Property(property="depth_cm", type="number", nullable=true, example=170),
 *     @OA\Property(property="height_cm", type="number", nullable=true, example=85),
 *     @OA\Property(property="weight_kg", type="number", nullable=true, example=75.5),
 *     @OA\Property(property="seat_height_cm", type="string", nullable=true, example="45cm"),
 *     @OA\Property(property="price", type="number", example=22000000, description="Giá riêng của biến thể này"),
 *     @OA\Property(property="stock_quantity", type="integer", example=5, description="Số lượng tồn kho"),
 *     @OA\Property(property="image_url", type="string", nullable=true, description="Ảnh riêng của biến thể"),
 *     @OA\Property(property="is_available", type="boolean", example=true, description="Trạng thái còn hàng (true/false)")
 * )
 */
class ProductVariantController extends Controller
{
    /**
     * @OA\Get(
     *     path="/products/{productId}/variants",
     *     summary="Lấy tất cả biến thể của một sản phẩm",
     *     tags={"Product Variants"},
     *     @OA\Parameter(name="productId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200, 
     *         description="Thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ProductVariant"))
     *         )
     *     ),
     *     @OA\Response(response=404, description="Sản phẩm không tồn tại")
     * )
     */
    public function index($productId)
    {
        if (!Product::where('id', $productId)->exists()) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm không tồn tại'], 404);
        }

        $variants = ProductVariant::where('product_id', $productId)->get();
        return response()->json(['success' => true, 'data' => $variants]);
    }

    /**
     * @OA\Post(
     *     path="/products/{productId}/variants",
     *     summary="Thêm biến thể mới cho sản phẩm (Admin)",
     *     tags={"Product Variants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="productId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"sku", "price"},
     *                 @OA\Property(property="sku", type="string", example="SOFA-BRN-2.8M"),
     *                 @OA\Property(property="price", type="number", example=22000000),
     *                 @OA\Property(property="stock_quantity", type="integer", example=5),
     *                 @OA\Property(property="color", type="string", example="Nâu cà phê"),
     *                 @OA\Property(property="wood_type", type="string", example="Gỗ sồi"),
     *                 @OA\Property(property="upholstery", type="string", example="Da bò thật"),
     *                 @OA\Property(property="finish", type="string", example="Walnut"),
     *                 @OA\Property(property="size", type="string", example="2.8m x 1.7m"),
     *                 @OA\Property(property="width_cm", type="number", example=280),
     *                 @OA\Property(property="depth_cm", type="number", example=170),
     *                 @OA\Property(property="height_cm", type="number", example=85),
     *                 @OA\Property(property="weight_kg", type="number", example=75.5),
     *                 @OA\Property(property="seat_height_cm", type="string", example="45cm"),
     *                 @OA\Property(property="is_available", type="integer", enum={0,1}, example=1),
     *                 @OA\Property(property="image", type="string", format="binary", description="Ảnh riêng của biến thể")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201, 
     *         description="Tạo thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/ProductVariant")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Sản phẩm không tồn tại"),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function store(StoreProductVariantRequest $request, $productId)
    {
        if (!Product::where('id', $productId)->exists()) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm không tồn tại'], 404);
        }

        $validated = $request->validated();
        $validated['product_id'] = $productId;

        if ($request->hasFile('image')) {
            $uploaded = cloudinary()->uploadApi()->upload($request->file('image')->getRealPath(), [
                'folder' => 'variants',
                'transformation' => [
                    'width' => 1000,
                    'crop' => 'limit',
                    'quality' => 'auto',
                    'fetch_format' => 'webp'
                ]
            ]);
            $validated['image_url'] = $uploaded['secure_url'];
        }

        $variant = ProductVariant::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Thêm biến thể thành công!',
            'data' => $variant
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/products/{productId}/variants/{variantId}",
     *     summary="Cập nhật biến thể sản phẩm (Admin)",
     *     description="[FE NOTE] Dùng POST thay vì PUT để hỗ trợ upload file. Thêm field `_method = PUT` vào FormData.",
     *     tags={"Product Variants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="productId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="variantId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="_method", type="string", example="PUT", description="[BẮT BUỘC] Method Spoofing"),
     *                 @OA\Property(property="price", type="number", example=20000000),
     *                 @OA\Property(property="stock_quantity", type="integer", example=10),
     *                 @OA\Property(property="color", type="string", example="Xám"),
     *                 @OA\Property(property="is_available", type="integer", enum={0,1}, example=1),
     *                 @OA\Property(property="image", type="string", format="binary", description="[TÙY CHỌN] Ảnh mới")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200, 
     *         description="Cập nhật thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cập nhật biến thể thành công!"),
     *             @OA\Property(property="data", ref="#/components/schemas/ProductVariant")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Biến thể không tồn tại"),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function update(UpdateProductVariantRequest $request, $productId, $variantId)
    {
        $variant = ProductVariant::where('product_id', $productId)->find($variantId);
        if (!$variant) {
            return response()->json(['success' => false, 'message' => 'Biến thể không tồn tại'], 404);
        }

        $validated = $request->validated();

        if ($request->hasFile('image')) {
            if ($oldUrl = $variant->getRawOriginal('image_url')) {
                if (Str::contains($oldUrl, 'res.cloudinary.com')) {
                    $parts = explode('/upload/', $oldUrl);
                    if (isset($parts[1])) {
                        $path = preg_replace('/^v\d+\//', '', $parts[1]); 
                        $publicId = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME);
                        if ($publicId && $publicId !== '.') cloudinary()->uploadApi()->destroy($publicId);
                    }
                } else {
                    Storage::disk('public')->delete($oldUrl);
                }
            }
            $uploaded = cloudinary()->uploadApi()->upload($request->file('image')->getRealPath(), [
                'folder' => 'variants',
                'transformation' => [
                    'width' => 1000,
                    'crop' => 'limit',
                    'quality' => 'auto',
                    'fetch_format' => 'webp'
                ]
            ]);
            $validated['image_url'] = $uploaded['secure_url'];
        }

        $variant->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật biến thể thành công!',
            'data' => $variant->fresh()
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/products/{productId}/variants/{variantId}",
     *     summary="Xóa biến thể sản phẩm (Admin)",
     *     tags={"Product Variants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="productId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="variantId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200, 
     *         description="Xóa thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đã xóa biến thể thành công!")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Biến thể không tồn tại")
     * )
     */
    public function destroy($productId, $variantId)
    {
        $variant = ProductVariant::where('product_id', $productId)->find($variantId);
        if (!$variant) {
            return response()->json(['success' => false, 'message' => 'Biến thể không tồn tại'], 404);
        }

        // Xóa ảnh của biến thể nếu có
        if ($oldUrl = $variant->getRawOriginal('image_url')) {
            if (Str::contains($oldUrl, 'res.cloudinary.com')) {
                $parts = explode('/upload/', $oldUrl);
                if (isset($parts[1])) {
                    $path = preg_replace('/^v\d+\//', '', $parts[1]); 
                    $publicId = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME);
                    if ($publicId && $publicId !== '.') cloudinary()->uploadApi()->destroy($publicId);
                }
            } else {
                Storage::disk('public')->delete($oldUrl);
            }
        }

        $variant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa biến thể thành công!'
        ]);
    }
}
