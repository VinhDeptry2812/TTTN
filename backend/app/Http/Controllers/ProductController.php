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
     *     summary="Lấy danh sách sản phẩm (Hỗ trợ Lọc, Sắp xếp & Infinite Scrolling)",
     *     description="API trả về danh sách sản phẩm với cursor-based pagination. Hỗ trợ lọc theo danh mục, khoảng giá và sắp xếp theo nhiều tiêu chí.",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Lọc theo ID danh mục sản phẩm",
     *         required=false,
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         description="Giá tối thiểu (lọc base_price >= giá trị này)",
     *         required=false,
     *         @OA\Schema(type="number", example=1000000)
     *     ),
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         description="Giá tối đa (lọc base_price <= giá trị này)",
     *         required=false,
     *         @OA\Schema(type="number", example=5000000)
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Trường sắp xếp. Chỉ chấp nhận: base_price, name, created_at (mặc định: created_at)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"base_price", "name", "created_at"}, default="created_at")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Thứ tự sắp xếp: asc (tăng dần) hoặc desc (giảm dần, mặc định)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Parameter(
     *         name="cursor",
     *         in="query",
     *         description="Mã cursor để lấy trang tiếp theo (lấy từ next_page_url của response trước)",
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
    public function index(Request $request)
    {
        $query = Product::with('category');

        // Admin gửi all=true → không lọc is_active, trả hết
        // FE public mặc định chỉ hiển thị sản phẩm đang bán
        if (!$request->boolean('all')) {
            $query->where('is_active', true);
        }

        // Lọc theo danh mục
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Tìm kiếm theo tên hoặc SKU
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Lọc theo khoảng giá
        if ($request->filled('min_price')) {
            $query->where('base_price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('base_price', '<=', $request->max_price);
        }

        // Sắp xếp
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['base_price', 'name', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        // Admin: phân trang truyền thống (10 SP/trang), hiển thị cả SP ẩn
        if ($request->boolean('all')) {
            $products = $query->orderBy('id')->paginate(10);
            $products->appends($request->only(['category_id', 'min_price', 'max_price', 'sort_by', 'sort_order', 'all']));
            return response()->json($products);
        }

        // Luôn thêm orderBy id để cursor pagination không bỏ sót dữ liệu
        $query->orderBy('id');

        $products = $query->cursorPaginate(12);

        // Giữ nguyên filter params trong next_page_url
        $products->appends($request->only(['category_id', 'min_price', 'max_price', 'sort_by', 'sort_order']));

        return response()->json($products);
    }

    /**
     * @OA\Get(
     *     path="/products/{id}",
     *     summary="Lấy chi tiết sản phẩm theo ID",
     *     tags={"Products"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công"),
     *     @OA\Response(response=404, description="Sản phẩm không tồn tại")
     * )
     */
    public function show($id)
    {
        $product = Product::with(['category', 'variants'])->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không tồn tại'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
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
        \Log::info('Product store hit', ['data' => $request->all()]);
        $validated = $request->validated();

        $validated['slug'] = Str::slug($validated['name']) . '-' . time();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = $path;
        }

        $product = Product::create($validated);
        \Log::info('Product created in DB', ['id' => $product->id]);

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
        ], 200);
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
