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
use App\Services\GeminiVisionService;
use App\Http\Requests\GeminiVisionRequest;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/**
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
 */
class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/products",
     *     summary="Lấy danh sách sản phẩm",
     *     description="API trả về danh sách sản phẩm có hỗ trợ tìm kiếm, lọc, sắp xếp và cursor-based pagination (infinite scrolling). 
     *     Mặc định chỉ trả về sản phẩm đang hoạt động (is_active = true). 
     *     Nếu truyền all=true thì trả về tất cả sản phẩm (dành cho admin).",
     *     operationId="getProducts",
     *     tags={"Products"},
     *
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="ID danh mục sản phẩm. Hệ thống sẽ tự động lấy cả danh mục con.",
     *         required=false,
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Tìm kiếm theo tên sản phẩm hoặc SKU",
     *         required=false,
     *         @OA\Schema(type="string", example="iphone")
     *     ),
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Tìm kiếm theo tên sản phẩm hoặc SKU",
     *         required=false,
     *         @OA\Schema(type="string", example="iphone")
     *     ),
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         description="Lọc sản phẩm có giá >= giá trị này",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=1000000)
     *     ),
     *
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         description="Lọc sản phẩm có giá <= giá trị này",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=5000000)
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Trường dùng để sắp xếp",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"base_price","name","created_at"},
     *             default="created_at"
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Thứ tự sắp xếp",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"asc","desc"},
     *             default="desc"
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="cursor",
     *         in="query",
     *         description="Cursor dùng cho cursor pagination để lấy trang tiếp theo",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="all",
     *         in="query",
     *         description="Nếu true thì trả về tất cả sản phẩm (bao gồm cả sản phẩm ẩn). Dành cho admin.",
     *         required=false,
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách sản phẩm",
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 description="Danh sách sản phẩm",
     *                 @OA\Items(ref="#/components/schemas/Product")
     *             ),
     *
     *             @OA\Property(
     *                 property="path",
     *                 type="string",
     *                 example="http://localhost/api/products"
     *             ),
     *
     *             @OA\Property(
     *                 property="per_page",
     *                 type="integer",
     *                 example=12,
     *                 description="Số lượng sản phẩm mỗi trang"
     *             ),
     *
     *             @OA\Property(
     *                 property="next_cursor",
     *                 type="string",
     *                 nullable=true,
     *                 example="eyJpZCI6MTJ9",
     *                 description="Cursor cho trang tiếp theo"
     *             ),
     *
     *             @OA\Property(
     *                 property="next_page_url",
     *                 type="string",
     *                 nullable=true,
     *                 example="http://localhost/api/products?cursor=eyJpZCI6MTJ9",
     *                 description="URL trang tiếp theo"
     *             ),
     *
     *             @OA\Property(
     *                 property="prev_cursor",
     *                 type="string",
     *                 nullable=true,
     *                 example=null,
     *                 description="Cursor trang trước"
     *             ),
     *
     *             @OA\Property(
     *                 property="prev_page_url",
     *                 type="string",
     *                 nullable=true,
     *                 example=null,
     *                 description="URL trang trước"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Tham số không hợp lệ"
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Product::select([
            'id', 'category_id', 'name', 'slug', 'sku', 'base_price', 'stock_quantity',
            'sale_price', 'image_url', 'is_active', 'is_featured', 'created_at'
        ])->with(['category:id,name,slug']);

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

            return response()->json($products->toArray());
        }

        // Luôn thêm orderBy id để cursor pagination không bỏ sót dữ liệu
        $query->orderBy('id');

        // Phân trang bằng cursor pagination (12 sản phẩm mỗi trang)
        $products = $query->cursorPaginate(12);

        return response()->json($products->toArray());
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
        $product = Product::with(['category', 'variants', 'images'])->find($id);
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
     *                 @OA\Property(property="sku", type="string", example="SKU12345"),
     *                 @OA\Property(property="category_id", type="integer", example=3)  ,
     *                 @OA\Property(property="stock_quantity", type="integer", example=10),
     *                 @OA\Property(property="image", type="string", format="binary", description="Ảnh đại diện (Sẽ được nén webp)"),
     *                 @OA\Property(property="gallery_images[]", type="array", @OA\Items(type="string", format="binary"), description="Danh sách ảnh phụ (Tối đa 5)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201, 
     *         description="Tạo thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sản phẩm đã được tạo thành công!"),
     *             @OA\Property(property="product", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Không có quyền truy cập"),
     *     @OA\Response(response=422, description="Lỗi validation")
     * )
     */
    public function store(StoreProductRequest $request)
    {
        $validatedData = $request->validated();

        $validatedData['slug'] = Str::slug($request->name) . '-' . time();

        if ($request->hasFile('image')) {
            $uploaded = cloudinary()->uploadApi()->upload($request->file('image')->getRealPath(), [
                'folder' => 'products',
                'transformation' => [
                    'width' => 1000,
                    'crop' => 'limit',
                    'quality' => 'auto',
                    'fetch_format' => 'webp'
                ]
            ]);
            $validatedData['image_url'] = $uploaded['secure_url'];
        }

        $product = Product::create($validatedData);

        // Tự động tạo variant default
        $product->variants()->create([
            'sku'            => $validatedData['sku'] . '-DEFAULT',
            'price'          => $validatedData['base_price'],
            'stock_quantity' => $validatedData['stock_quantity'] ?? 0,
            'image_url'      => $validatedData['image_url'] ?? null,
            'is_available'   => true,
        ]);

        // Upload gallery images
        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $index => $galleryImage) {
                $uploaded = cloudinary()->uploadApi()->upload($galleryImage->getRealPath(), [
                    'folder' => 'products_gallery',
                    'transformation' => [
                        'width' => 1000,
                        'crop' => 'limit',
                        'quality' => 'auto',
                        'fetch_format' => 'webp'
                    ]
                ]);
                
                $product->images()->create([
                    'image_url' => $uploaded['secure_url'],
                    'is_primary' => false,
                    'sort_order' => $index
                ]);
            }
        }

        $product->load('images', 'variants');

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
     *                 @OA\Property(property="sale_price", type="number", nullable=true),
     *                 @OA\Property(property="category_id", type="integer"),
     *                 @OA\Property(property="stock_quantity", type="integer"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Ảnh đại diện mới"),
     *                 @OA\Property(property="gallery_images[]", type="array", @OA\Items(type="string", format="binary"), description="Danh sách ảnh phụ mới (Sẽ ghi đè ảnh cũ)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200, 
     *         description="Cập nhật thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sản phẩm đã được cập nhật thành công!"),
     *             @OA\Property(property="product", ref="#/components/schemas/Product")
     *         )
     *     ),
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
            // Delete old primary image correctly using raw path
            if ($oldUrl = $product->getRawOriginal('image_url')) {
                if (\Illuminate\Support\Str::contains($oldUrl, 'res.cloudinary.com')) {
                    $parts = explode('/upload/', $oldUrl);
                    if (isset($parts[1])) {
                        $path = preg_replace('/^v\d+\//', '', $parts[1]); 
                        $publicId = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME);
                        if ($publicId && $publicId !== '.') {
                            cloudinary()->uploadApi()->destroy($publicId);
                        }
                    }
                } else {
                    Storage::disk('public')->delete($oldUrl);
                }
            }
            
            $uploaded = cloudinary()->uploadApi()->upload($request->file('image')->getRealPath(), [
                'folder' => 'products',
                'transformation' => [
                    'width' => 1000,
                    'crop' => 'limit',
                    'quality' => 'auto',
                    'fetch_format' => 'webp'
                ]
            ]);
            $validatedData['image_url'] = $uploaded['secure_url'];
        }

        $product->update($validatedData);

        if ($request->hasFile('gallery_images')) {
            // Replace all existing gallery images
            foreach ($product->images as $oldImage) {
                if ($oldUrl = $oldImage->getRawOriginal('image_url')) {
                    if (\Illuminate\Support\Str::contains($oldUrl, 'res.cloudinary.com')) {
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
                $oldImage->delete(); // Or permanent delete since the record logic uses soft delete maybe
            }

            foreach ($request->file('gallery_images') as $index => $galleryImage) {
                $uploaded = cloudinary()->uploadApi()->upload($galleryImage->getRealPath(), [
                    'folder' => 'products_gallery',
                    'transformation' => [
                        'width' => 1000,
                        'crop' => 'limit',
                        'quality' => 'auto',
                        'fetch_format' => 'webp'
                    ]
                ]);
                
                $product->images()->create([
                    'image_url' => $uploaded['secure_url'],
                    'is_primary' => false,
                    'sort_order' => $index
                ]);
            }
        }

        $product->load('images');

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
     *     @OA\Response(
     *         response=200, 
     *         description="Xóa thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sản phẩm đã được xóa thành công!")
     *         )
     *     ),
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
        // 1 query duy nhất lấy tất cả categories (thay vì đệ quy N queries)
        $allCategories = Category::pluck('parent_id', 'id');

        $ids = [(int) $categoryId];
        $queue = [(int) $categoryId];

        // BFS: duyệt breadth-first tìm tất cả danh mục con
        while (!empty($queue)) {
            $currentId = array_shift($queue);
            foreach ($allCategories as $id => $parentId) {
                if ($parentId == $currentId && !in_array($id, $ids)) {
                    $ids[] = $id;
                    $queue[] = $id;
                }
            }
        }

        return $ids;
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

    /**
     * @OA\Post(
     *     path="/products/detect-ai",
     *     summary="Nhận diện sản phẩm từ hình ảnh sử dụng Gemini Vision",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="image",
     *                     type="string",
     *                     format="binary",
     *                     description="File hình ảnh sản phẩm (jpg, png...)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Kết quả nhận diện thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="is_furniture", type="boolean", example=true),
     *             @OA\Property(property="name", type="string", description="Tên mẫu sản phẩm", example="Sofa Ý Luxury"),
     *             @OA\Property(property="category", type="string", description="Danh mục", example="Sofa"),
     *             @OA\Property(property="style", type="string", description="Phong cách", example="Hiện đại"),
     *             @OA\Property(property="description_raw", type="string", description="Mô tả AI tự tạo"),
     *             @OA\Property(property="material", type="string", description="Chất liệu", example="Da bò thật"),
     *             @OA\Property(property="color", type="string", description="Màu sắc", example="Nâu"),
     *             @OA\Property(property="weight_kg", type="number", description="Trọng lượng (kg)", example=45.5),
     *             @OA\Property(property="finish", type="string", description="Màu hoàn thiện", example="Matte"),
     *             @OA\Property(property="size", type="string", description="Kích thước tổng quát", example="Lớn"),
     *             @OA\Property(property="width_cm", type="number", description="Chiều rộng (cm)", example=200),
     *             @OA\Property(property="depth_cm", type="number", description="Chiều sâu (cm)", example=90),
     *             @OA\Property(property="height_cm", type="number", description="Chiều cao (cm)", example=85),
     *             @OA\Property(property="seat_height_cm", type="number", description="Chiều cao mặt ghế (cm)", example=45),
     *             @OA\Property(property="price", type="number", description="Giá dự kiến (VND)", example=15000000)
     *         )
     *     ),
     *     @OA\Response(response=400, description="Dữ liệu không hợp lệ"),
     *     @OA\Response(response=500, description="Lỗi hệ thống hoặc API")
     * )
     */
    public function detectAI(GeminiVisionRequest $request, GeminiVisionService $geminiVision)
    {
        $image = $request->file('image');
        $path = $image->getRealPath();
        $mimeType = $image->getMimeType();

        $result = $geminiVision->identifyProductFromImage($path, $mimeType);

        if (!$result) {
            return response()->json(['message' => 'Không thể nhận diện hình ảnh lúc này'], 500);
        }

        // Kiểm tra nếu không phải đồ nội thất
        if (isset($result['is_furniture']) && $result['is_furniture'] === false) {
            return response()->json([
                'message' => $result['error'] ?? 'Hình ảnh không phải là đồ nội thất.'
            ], 422);
        }

        return response()->json($result);
    }
}
