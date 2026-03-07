<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="Category",
 *     title="Category",
 *     description="Mô hình Danh mục sản phẩm",
 *     @OA\Property(property="id", type="integer", example=1, description="ID duy nhất của danh mục"),
 *     @OA\Property(property="name", type="string", example="Nội thất phòng khách", description="Tên hiển thị của danh mục"),
 *     @OA\Property(property="slug", type="string", example="noi-that-phong-khach-65e8a1", description="Đường dẫn thân thiện (tự động tạo từ tên + unique id)"),
 *     @OA\Property(property="parent_id", type="integer", nullable=true, example=null, description="ID của danh mục cha (null nếu là danh mục gốc)"),
 *     @OA\Property(property="image", type="string", nullable=true, example="categories/image.jpg", description="Đường dẫn ảnh danh mục (lưu trong storage)"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Mô tả danh mục", description="Mô tả chi tiết về danh mục"),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Trạng thái hiển thị (true: hiện, false: ẩn)"),
 *     @OA\Property(property="sort_order", type="integer", example=0, description="Thứ tự sắp xếp (số càng nhỏ càng hiện lên đầu)"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Ngày tạo"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Ngày cập nhật gần nhất")
 * )
 */
class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/categories",
     *     summary="Lấy danh sách danh mục",
     *     description="API trả về danh sách danh mục. Có thể lấy theo cấu trúc cây hoặc danh sách phẳng. Mặc định chỉ lấy danh mục đang hoạt động.",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="tree",
     *         in="query",
     *         description="Nếu bằng 1, trả về cấu trúc cây (lồng nhau)",
     *         required=false,
     *         @OA\Schema(type="integer", enum={0, 1}, default=0)
     *     ),
     *     @OA\Parameter(
     *         name="all",
     *         in="query",
     *         description="Nếu bằng 1, lấy cả danh mục bị ẩn (Admin dùng)",
     *         required=false,
     *         @OA\Schema(type="integer", enum={0, 1}, default=0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thành công",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Category"))
     *     )
     * )
     */
    public function index(Request $request)
    {
        $showAll = $request->boolean('all');

        if ($request->boolean('tree')) {
            // Lấy danh mục gốc kèm theo các con của chúng
            $query = Category::with([
                'children' => function ($q) use ($showAll) {
                    if (!$showAll) {
                        $q->where('is_active', 1)->orderBy('sort_order');
                    } else {
                        $q->orderBy('sort_order');
                    }
                }
            ])
                ->whereNull('parent_id');

            if (!$showAll) {
                $query->where('is_active', 1);
            }

            $categories = $query->orderBy('sort_order')->get();
        } else {
            // Lấy danh sách phẳng (cho dropdown chọn cha)
            $query = Category::with('parent');

            if (!$showAll) {
                $query->where('is_active', 1);
            }

            $categories = $query->orderBy('sort_order')->get();
        }

        return response()->json($categories);
    }

    /**
     * @OA\Post(
     *     path="/categories",
     *     summary="Tạo danh mục mới (Yêu cầu: superadmin, admin)",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name"},
     *                 @OA\Property(property="name", type="string", example="Danh mục mới", description="Tên danh mục"),
     *                 @OA\Property(property="parent_id", type="integer", nullable=true, description="ID danh mục cha (để trống hoặc 0 nếu là gốc)"),
     *                 @OA\Property(property="image", type="string", format="binary", description="File ảnh danh mục"),
     *                 @OA\Property(property="description", type="string", description="Mô tả ngắn"),
     *                 @OA\Property(property="is_active", type="integer", enum={0, 1}, default=1, description="Trạng thái (1: Hiện, 0: Ẩn)"),
     *                 @OA\Property(property="sort_order", type="integer", default=0, description="Thứ tự hiển thị (càng nhỏ càng ưu tiên)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tạo thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/Category")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Không có quyền truy cập"),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function store(StoreCategoryRequest $request)
    {
        if ($request->has('parent_id')) {
            $val = $request->parent_id;
            if ($val === '' || $val === 'null' || $val === 'undefined' || $val === '0' || $val === 0) {
                $request->merge(['parent_id' => null]);
            }
        }

        $validated = $request->validated();

        $validated['slug'] = Str::slug($validated['name']) . '-' . uniqid();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('categories', 'public');
            $validated['image'] = $path;
        }

        $category = Category::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo danh mục thành công',
            'data' => $category
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/categories/{id}",
     *     summary="Chi tiết danh mục",
     *     tags={"Categories"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Thành công",
     *         @OA\JsonContent(ref="#/components/schemas/Category")
     *     ),
     *     @OA\Response(response=404, description="Không tìm thấy")
     * )
     */
    public function show($id)
    {
        $category = Category::with([
            'parent',
            'children' => function ($q) {
                $q->withCount('products');
            }
        ])->findOrFail($id);

        return response()->json($category);
    }

    /**
     * @OA\Post(
     *     path="/categories/{id}",
     *     summary="Cập nhật danh mục (Yêu cầu: superadmin, admin)",
     *     description="Vì Laravel không hỗ trợ multipart/form-data qua PUT tốt, nên dùng POST kèm _method=PUT",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name"},
     *                 @OA\Property(property="_method", type="string", example="PUT"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="parent_id", type="integer", nullable=true),
     *                 @OA\Property(property="image", type="string", format="binary"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="is_active", type="integer", enum={0, 1}),
     *                 @OA\Property(property="sort_order", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Category")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Không có quyền truy cập")
     * )
     */
    public function update(UpdateCategoryRequest $request, $id)
    {
        if ($request->has('parent_id')) {
            $val = $request->parent_id;
            if ($val === '' || $val === 'null' || $val === 'undefined' || $val === '0' || $val === 0) {
                $request->merge(['parent_id' => null]);
            }
        }

        $category = Category::findOrFail($id);

        $validated = $request->validated();

        $validated['slug'] = Str::slug($validated['name']) . '-' . substr($id, -4);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('categories', 'public');
            $validated['image'] = $path;
        }

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật danh mục thành công',
            'data' => $category
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/categories/{id}",
     *     summary="Xóa danh mục (Yêu cầu: superadmin, admin)",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Xóa thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Không có quyền truy cập"),
     *     @OA\Response(response=422, description="Không thể xóa vì còn sản phẩm")
     * )
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Kiểm tra xem có sản phẩm nào đang thuộc danh mục này không
        $productCount = $category->products()->count();
        if ($productCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Không thể xóa danh mục này vì đang có $productCount sản phẩm thuộc về nó."
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa danh mục thành công'
        ]);
    }
}
