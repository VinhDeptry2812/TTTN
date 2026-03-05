<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * @OA\Post(
     *     path="/admin/login",
     *     summary="Đăng nhập Admin",
     *     tags={"Admin"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", example="admin@gmail.com"),
     *             @OA\Property(property="password", type="string", example="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Đăng nhập thành công"),
     *     @OA\Response(response=401, description="Sai email hoặc mật khẩu")
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('admin-api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Email hoặc mật khẩu admin không đúng'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'token' => $token,
            'admin' => auth('admin-api')->user()
        ]);
    }

    public function logout()
    {
        auth('admin-api')->logout();
        return response()->json(['success' => true, 'message' => 'Admin logout thành công']);
    }

    public function me()
    {
        return response()->json([
            'success' => true,
            'admin' => auth('admin-api')->user()
        ]);
    }

    public function index()
    {
        // ... (code cũ)
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
