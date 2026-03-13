<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeminiRequestGenerateDesc;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Support\Str;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use App\Services\GeminiService;

/**
 * @OA\Info(
 *     title="API HỆ THỐNG NỘI THẤT NHÓM MÌNH",
 *     version="1.0.0",
 *     description="Hệ thống API hỗ trợ phân quyền người dùng (RBAC). 
 *     
 *     ### Danh sách Vai trò (Roles):
 *     - **superadmin**: Quyền cao nhất, quản lý toàn bộ hệ thống bao gồm cả nhân viên và khách hàng.
 *     - **admin**: Quản lý nghiệp vụ chính (Sản phẩm, Danh mục, Đơn hàng).
 *     - **staff**: Nhân viên vận hành, xem báo cáo và thực hiện các tác vụ được chỉ định.
 *     - **user**: Khách hàng (mặc định)."
 * )
 * 
 * @OA\Schema(
 *     schema="Product",
 *     title="Product",
 *     description="Mô hình Sản phẩm",
 *     @OA\Property(property="id", type="integer", example=1, description="ID duy nhất của sản phẩm"),
 *     @OA\Property(property="category_id", type="integer", example=3, description="ID của danh mục thuộc về"),
 *     @OA\Property(property="name", type="string", example="Ghế Sofa Da Cao Cấp", description="Tên sản phẩm"),
 *     @OA\Property(property="slug", type="string", example="ghe-sofa-da-cao-cap-123456", description="Đường dẫn thân thiện (unique)"),
 *     @OA\Property(property="sku", type="string", example="SOFA-001", description="Mã kho hàng (Stock Keeping Unit)"),
 *     @OA\Property(property="description", type="string", description="Mô tả chi tiết sản phẩm"),
 *     @OA\Property(property="material", type="string", example="Da bò, Gỗ sồi", description="Chất liệu cấu thành"),
 *     @OA\Property(property="brand", type="string", example="Luxe Interiors", description="Thương hiệu"),
 *     @OA\Property(property="base_price", type="number", example=5000000, description="Giá gốc niêm yết"),
 *     @OA\Property(property="sale_price", type="number", nullable=true, example=4500000, description="Giá khuyến mãi (nếu có)"),
 *     @OA\Property(property="image_url", type="string", description="Đường dẫn ảnh chính"),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Trạng thái kinh doanh (hiện/ẩn)"),
 *     @OA\Property(property="is_featured", type="boolean", example=false, description="Sản phẩm nổi bật/xu hướng"),
 *     @OA\Property(property="category", ref="#/components/schemas/Category"),
 *     @OA\Property(property="variants", type="array", @OA\Items(ref="#/components/schemas/ProductVariant"))
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
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Product")),
     *             @OA\Property(property="path", type="string", description="URL hiện tại"),
     *             @OA\Property(property="per_page", type="integer", description="Số lượng mỗi trang"),
     *             @OA\Property(property="next_cursor", type="string", nullable=true, description="Cursor cho trang tiếp theo"),
     *             @OA\Property(property="next_page_url", type="string", nullable=true, description="Link trang tiếp theo"),
     *             @OA\Property(property="prev_cursor", type="string", nullable=true, description="Cursor cho trang trước"),
     *             @OA\Property(property="prev_page_url", type="string", nullable=true, description="Link trang trước")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Product::with('category');

        // Admin gửi all=true -> không lọc is_active, trả hết
        // FE public mặc định chỉ hiển thị sản phẩm đang bán
        if (!$request->boolean('all')) {
            $query->where('is_active', true);
        }

        // Lọc theo danh mục (bao gồm cả danh mục con)
        if ($request->filled('category_id')) {
            $allCategoryIds = $this->getAllChildCategoryIds($request->category_id);
            $query->whereIn('category_id', $allCategoryIds);
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

        // Phân trang bằng cursor pagination (12 sản phẩm mỗi trang)
        $products = $query->cursorPaginate(12);

        return response()->json($products);
    }

    /**
     * @OA\Get(
     *     path="/products/{id}",
     *     summary="Chi tiết sản phẩm",
     *     tags={"Products"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200, 
     *         description="Thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
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

    /**
     * @OA\Post(
     *     path="/products/create",
     *     summary="Tạo sản phẩm mới (Yêu cầu: superadmin, admin)",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "base_price", "category_id"},
     *                 @OA\Property(property="name", type="string", example="Ghế Sofa Da Cao Cấp"),
     *                 @OA\Property(property="description", type="string", example="Mô tả chi tiết sản phẩm"),
     *                 @OA\Property(property="base_price", type="number", example=5000000),
     *                 @OA\Property(property="sale_price", type="number", nullable=true),
     *                 @OA\Property(property="category_id", type="integer", example=3),
     *                 @OA\Property(property="image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tạo thành công"),
     *     @OA\Response(response=403, description="Không có quyền truy cập"),
     *     @OA\Response(response=422, description="Lỗi validation")
     * )
     */
    public function store(StoreProductRequest $request)
    {
        $validatedData = $request->validated();

        $validatedData['slug'] = Str::slug($request->name) . '-' . time();

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
            $validatedData['image_url'] = $imagePath;
        }

        $product = Product::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Sản phẩm đã được tạo thành công!',
            'product' => $product
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/products/{id}",
     *     summary="Cập nhật sản phẩm (Yêu cầu: superadmin, admin)",
     *     description="Vì Laravel không hỗ trợ file upload qua PUT trực tiếp một cách tốt nhất, hãy dùng POST kèm tham số _method=PUT",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="_method", type="string", example="PUT"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="base_price", type="number"),
     *                 @OA\Property(property="category_id", type="integer"),
     *                 @OA\Property(property="image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công"),
     *     @OA\Response(response=403, description="Không có quyền truy cập")
     * )
     */
    public function update(UpdateProductRequest $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm không tồn tại'], 404);
        }

        $validatedData = $request->validated();

        if ($request->hasFile('image')) {
            if ($product->image_url) {
                Storage::disk('public')->delete($product->image_url);
            }
            $imagePath = $request->file('image')->store('products', 'public');
            $validatedData['image_url'] = $imagePath;
        }

        $product->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Sản phẩm đã được cập nhật thành công!',
            'product' => $product
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/products/{id}",
     *     summary="Xóa sản phẩm (Yêu cầu: superadmin, admin)",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công"),
     *     @OA\Response(response=403, description="Không có quyền truy cập"),
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

    /**
     * Lấy tất cả ID của danh mục con (đệ quy)
     */
    private function getAllChildCategoryIds($categoryId)
    {
        $ids = [(int) $categoryId];
        $children = Category::where('parent_id', $categoryId)->pluck('id')->toArray();

        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getAllChildCategoryIds($childId));
        }

        return array_unique($ids);
    }


    
    /**
     * @OA\Post(
     *     path="/products/generate-ai-description",
     *     summary="Tạo mô tả sản phẩm bằng AI (Yêu cầu: superadmin, admin)",
     *     description="Sử dụng OpenAI để tạo một đoạn mô tả sản phẩm hấp dẫn dựa trên tên, danh mục và chất liệu.",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Ghế Sofa Da Ý Luxury"),
     *             @OA\Property(property="category", type="string", example="Sofa"),
     *             @OA\Property(property="material", type="string", example="Da bò thật, khung gỗ sồi")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tạo mô tả thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="description", type="string", example="Trải nghiệm sự sang trọng tột bậc với Ghế Sofa Da Ý Luxury...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Lỗi validation dữ liệu",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lỗi validation dữ liệu"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi kết nối API AI",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Không thể tạo mô tả lúc này")
     *         )
     *     )
     * )
     */
    public function generateAIDescription(GeminiRequestGenerateDesc $request, GeminiService $gemini)
    {
        $validatedData = $request->validated();
        $description = $gemini->generateDescription(
            $validatedData['name'],
            $validatedData['category'],
            $validatedData['material']
        );
        if (!$description) {
            return response()->json(['message' => 'Không thể tạo mô tả lúc này'], 500);
        }
        return response()->json(['description' => $description]);
    }
}
