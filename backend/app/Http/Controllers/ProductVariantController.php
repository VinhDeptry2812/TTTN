<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Http\Requests\StoreProductVariantRequest;
use App\Http\Requests\UpdateProductVariantRequest;
use App\Models\ProductImage;
use App\Services\ImageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


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
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }
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

        $variants = ProductVariant::where('product_id', $productId)
            ->with('images') // Eager load gallery images
            ->get();
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
     *                 @OA\Property(property="image", type="string", format="binary", description="Ảnh riêng của biến thể"),
     *                 @OA\Property(property="gallery_images[]", type="array", @OA\Items(type="string", format="binary"), description="Ảnh gallery cho biến thể")
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

        DB::beginTransaction();

        try {
            if ($request->hasFile('image')) {
                $imagePath = $this->imageService->uploadAndProcess($request->file('image'), 'variants');
                if ($imagePath) {
                    $validated['image_url'] = $imagePath;
                }
            }

            $variant = ProductVariant::create($validated);

            // Xử lý gallery_images cho biến thể
            if ($request->hasFile('gallery_images')) {
                foreach ($request->file('gallery_images') as $index => $galleryImage) {
                    $imagePath = $this->imageService->uploadAndProcess($galleryImage, 'variants');
                    
                    if ($imagePath) {
                        $variant->images()->create([
                            'product_id' => $variant->product_id,
                            'image_url' => $imagePath,
                            'is_primary' => false,
                            'sort_order' => $index
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Thêm biến thể thành công!',
                'data' => $variant->load('images')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi thêm biến thể: ' . $e->getMessage()
            ], 500);
        }
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
     *                 @OA\Property(property="image", type="string", format="binary", description="[TÙY CHỌN] Ảnh đại diện mới"),
     *                 @OA\Property(property="gallery_images[]", type="array", @OA\Items(type="string", format="binary"), description="[TÙY CHỌN] Thêm ảnh gallery mới"),
     *                 @OA\Property(property="delete_gallery_ids", type="string", example="[1,2,3]", description="[TÙY CHỌN] JSON array IDs của ảnh gallery cần xóa")
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

        DB::beginTransaction();

        try {
            if ($request->hasFile('image')) {
                // Delete old image using service
                $this->imageService->deleteImage($variant->getRawOriginal('image_url'));

                $imagePath = $this->imageService->uploadAndProcess($request->file('image'), 'variants');
                if ($imagePath) {
                    $validated['image_url'] = $imagePath;
                }
            }

            $variant->update($validated);

            // Xóa ảnh gallery được chỉ định
            if ($request->has('delete_gallery_ids')) {
                $deleteIds = $request->delete_gallery_ids;
                
                // Handle both JSON string (FormData) and Array (Direct JSON)
                if (is_string($deleteIds)) {
                    $deleteIds = json_decode($deleteIds, true);
                }

                if (is_array($deleteIds) && count($deleteIds) > 0) {
                    $imagesToDelete = $variant->images()->whereIn('id', $deleteIds)->get();
                    foreach ($imagesToDelete as $oldImage) {
                        $this->imageService->deleteImage($oldImage->getRawOriginal('image_url'));
                        $oldImage->forceDelete();
                    }
                }
            }

            // Xử lý thêm gallery images mới cho biến thể
            if ($request->hasFile('gallery_images')) {
                $maxSortOrder = $variant->images()->max('sort_order') ?? -1;

                foreach ($request->file('gallery_images') as $index => $galleryImage) {
                    $imagePath = $this->imageService->uploadAndProcess($galleryImage, 'variants');
                    
                    if ($imagePath) {
                        $variant->images()->create([
                            'product_id' => $variant->product_id,
                            'image_url' => $imagePath,
                            'is_primary' => false,
                            'sort_order' => $maxSortOrder + $index + 1
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật biến thể thành công!',
                'data' => $variant->fresh()->load('images')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật biến thể: ' . $e->getMessage()
            ], 500);
        }
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

        DB::beginTransaction();

        try {
            // Xóa ảnh đại diện biến thể
            $this->imageService->deleteImage($variant->getRawOriginal('image_url'));

            // Xóa tất cả ảnh trong gallery của biến thể
            $galleryImages = $variant->images()->get();
            foreach ($galleryImages as $galleryImage) {
                $this->imageService->deleteImage($galleryImage->getRawOriginal('image_url'));
                $galleryImage->forceDelete();
            }

            $variant->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa biến thể thành công!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa biến thể: ' . $e->getMessage()
            ], 500);
        }
    }
}
