<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Info(
 *     title="API WEB MÌNH",
 *     version="1.0.0"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/products",
     *     summary="Lấy danh sách sản phẩm (Infinite Scrolling)",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Số lượng sản phẩm mỗi trang (mặc định 12)",
     *         required=false,
     *         @OA\Schema(type="integer", default=12)
     *     ),
     *     @OA\Parameter(
     *         name="cursor",
     *         in="query",
     *         description="Mã cursor để đánh dấu vị trí trang tiếp theo (Lấy từ next_page_url của response trước)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Trả về danh sách sản phẩm thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="path", type="string"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="next_cursor", type="string", nullable=true),
     *             @OA\Property(property="next_page_url", type="string", nullable=true),
     *             @OA\Property(property="prev_cursor", type="string", nullable=true),
     *             @OA\Property(property="prev_page_url", type="string", nullable=true)
     *         )
     *     )
     * )
     */
    public function index()
    {
        $products = Product::with('category')->cursorPaginate(12);
        return response()->json($products);
    }

    public function getUsers()
    {
        $users = User::select('id', 'name', 'email')->get();
        return view('products', compact('users'));
    }

    /**
     * @OA\Post(
     *     path="/products/create",
     *     summary="Tạo sản phẩm mới (Admin)",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "category_id", "base_price", "sku"},
     *                 @OA\Property(property="name", type="string", example="Bàn Trà Sofa Cao Cấp"),
     *                 @OA\Property(property="category_id", type="integer", example=5),
     *                 @OA\Property(property="base_price", type="number", example=2500000),
     *                 @OA\Property(property="sale_price", type="number", example=2000000),
     *                 @OA\Property(property="sku", type="string", example="TAB-SOF-001"),
     *                 @OA\Property(property="material", type="string", example="Gỗ Óc Chó"),
     *                 @OA\Property(property="brand", type="string", example="Luxe Furniture"),
     *                 @OA\Property(property="description", type="string", example="Mô tả abc xyz"),
     *                 @OA\Property(property="is_active", type="integer", enum={0, 1}, example=1),
     *                 @OA\Property(property="is_featured", type="integer", enum={0, 1}, example=1),
     *                 @OA\Property(property="image", type="string", format="binary", description="File ảnh")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tạo thành công"),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        $validated['slug'] = Str::slug($validated['name']) . '-' . time();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = $path;
        }

        $product = Product::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Thêm sản phẩm thành công!',
            'data' => $product
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/products/{id}",
     *     summary="Cập nhật sản phẩm (Admin)",
     *     description="[FE NOTE] API này thực chất là PUT nhưng phải dùng method POST để hỗ trợ upload file (multipart/form-data). Để Laravel hiểu đây là PUT, PHẢI thêm field ẩn `_method = PUT` vào FormData khi gọi từ Frontend (React/Axios). Không cần làm gì thêm ở phía Backend.",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="_method", type="string", example="PUT", description="[BẮT BUỘC] Luôn điền 'PUT' vào đây. Đây là cơ chế Method Spoofing của Laravel."),
     *                 @OA\Property(property="name", type="string", example="Sofa Mới"),
     *                 @OA\Property(property="base_price", type="number", example=2500000),
     *                 @OA\Property(property="sale_price", type="number", example=1800000),
     *                 @OA\Property(property="material", type="string", example="Gỗ sồi"),
     *                 @OA\Property(property="brand", type="string", example="LuxHome"),
     *                 @OA\Property(property="is_active", type="integer", enum={0, 1}, example=1),
     *                 @OA\Property(property="is_featured", type="integer", enum={0, 1}, example=0),
     *                 @OA\Property(property="image", type="string", format="binary", description="[TÙY CHỌN] Ảnh mới. Nếu không gửi, ảnh cũ sẽ được giữ nguyên.")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công"),
     *     @OA\Response(response=404, description="Sản phẩm không tồn tại"),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function update(UpdateProductRequest $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm không tồn tại'], 404);
        }

        $validated = $request->validated();

        // Nếu có ảnh mới thì upload và xóa ảnh cũ
        if ($request->hasFile('image')) {
            // Xóa ảnh cũ nếu tồn tại
            if ($product->image_url) {
                Storage::disk('public')->delete($product->image_url);
            }
            $validated['image_url'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật sản phẩm thành công!',
            'data' => $product->fresh()
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/products/{id}",
     *     summary="Xóa sản phẩm (Admin - Soft Delete)",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công"),
     *     @OA\Response(response=404, description="Sản phẩm không tồn tại")
     * )
     */
    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm không tồn tại'], 404);
        }

        // Soft delete - chỉ đánh dấu xóa, không xóa hẳn ra khỏi CSDL
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sản phẩm đã được xóa thành công!'
        ]);
    }
}
