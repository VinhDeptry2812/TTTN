<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="API WEB MÌNH",
 *     version="1.0.0"
 * )
 */
class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/products",
     *     summary="Lấy danh sách sản phẩm",
     *     tags={"Products"},
     *     @OA\Response(
     *         response=200,
     *         description="Thành công"
     *     )
     * )
     */
    public function index()
    {
        $products = Product::with('category')->get();
        return response()->json($products);
    }

    public function index_json()
    {
        $products = Product::with('category')->get();
        return view('products', compact('products'));
    }

    /**
     * @OA\Post(
     *     path="/products/create",
     *     summary="Tạo sản phẩm mới",
     *     tags={"Products"},
     *     @OA\Response(
     *         response=201,
     *         description="Tạo thành công"
     *     )
     * )
     */
    public function store() {}

    /**
     * @OA\Put(
     *     path="/products/{id}",
     *     summary="Cập nhật sản phẩm",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật thành công"
     *     )
     * )
     */
    public function update() {}

    /**
     * @OA\Delete(
     *     path="/products/{id}",
     *     summary="Xóa sản phẩm",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Xóa thành công"
     *     )
     * )
     */
    public function destroy() {}
}
