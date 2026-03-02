<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;



class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/register",
     *     summary="Đăng ký tài khoản",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="Nguyen Van A"),
     *             @OA\Property(property="email", type="string", example="user@gmail.com"),
     *             @OA\Property(property="password", type="string", example="123456"),
     *             @OA\Property(property="password_confirmation", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Đăng ký thành công"),
     *     @OA\Response(response=422, description="Validation lỗi")
     * )
     */
    // SIGNUP
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email:rfc,dns|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'required' => ':attribute không được để trống.',
            'email' => ':attribute không đúng định dạng.',
            'min' => ':attribute phải có ít nhất :min ký tự.',
            'unique' => ':attribute đã tồn tại trong hệ thống.',
        ], [
            // Tên hiển thị thay thế cho tên field
            'name' => 'Họ tên',
            'email' => 'Email',
            'password' => 'Mật khẩu',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation lỗi',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = Auth::login($user);

        return response()->json([
            'success' => true,
            'message' => 'Đăng ký thành công',
            'token' => $token,
            'user' => $user
        ], 201);
    }


    /**
     * @OA\Post(
     *     path="/login",
     *     summary="Đăng nhập",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", example="user@gmail.com"),
     *             @OA\Property(property="password", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Đăng nhập thành công"),
     *     @OA\Response(response=401, description="Sai email hoặc mật khẩu")
     * )
     */
    // LOGIN
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email:rfc,dns',
            'password' => 'required|string|min:6',
        ], [
            'required' => ':attribute không được để trống.',
            'email' => ':attribute không đúng định dạng.',
            'min' => ':attribute phải có ít nhất :min ký tự.',
        ], [
            'email' => 'Email',
            'password' => 'Mật khẩu',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation lỗi',
                'errors' => $validator->errors()
            ], 422);
        }
        $credentials = $request->only('email', 'password');

        if (!$token = Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Email hoặc mật khẩu không đúng'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'token' => $token,
            'user' => Auth::user()
        ]);
    }
    /**
     * @OA\Post(
     *     path="/logout",
     *     summary="Đăng xuất",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true
     *     ),
     *     @OA\Response(response=200, description="Đăng xuất thành công"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    // LOGOUT
    public function logout()
    {
        try {
            Auth::logout(); // invalidate token
            return response()->json(['success' => true, 'message' => 'Đăng xuất thành công']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
    }

    // LẤY THÔNG TIN USER ĐANG ĐĂNG NHẬP
    public function me()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'user' => $user
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token không hợp lệ'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token đã hết hạn'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
    }

    /**
     * @OA\Post(
     *     path="/forgot-password",
     *     summary="Quên mật khẩu - gửi link đặt lại qua email",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", example="user@gmail.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Gửi email thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Link đặt lại mật khẩu đã được gửi vào email của bạn.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation lỗi",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="string", example="Email không tồn tại trong hệ thống.")
     *         )
     *     )
     * )
     */
    // ========================
    // QUÊN MẬT KHẨU - gửi mail
    // ========================
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email:rfc,dns|exists:users,email',
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.exists' => 'Email không tồn tại trong hệ thống.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->first()
            ], 422);
        }
        // Tạo token ngẫu nhiên
        $token = Str::random(64);
        // Lưu vào DB (xóa cũ nếu có)
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => hash('sha256', $token), // lưu đã hash
            'created_at' => now(),
        ]);
        // Link gửi cho user (trỏ về frontend)
        $resetLink = env('FRONTEND_URL', 'https://tttn-2.onrender.com/resetpassword')
            . '?token=' . $token
            . '&email=' . urlencode($request->email);
        // Gửi mail
        try {
            Mail::to($request->email)->send(new ResetPasswordMail($resetLink));
        } catch (\Exception $e) {
            \Log::error('Mail Reset Password Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi gửi email: ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Link đặt lại mật khẩu đã được gửi vào email của bạn.',
        ]);
    }
    /**
     * @OA\Post(
     *     path="/reset-password",
     *     summary="Đặt lại mật khẩu mới",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","token","password","password_confirmation"},
     *             @OA\Property(property="email", type="string", example="user@gmail.com"),
     *             @OA\Property(property="token", type="string", example="abc123xyz..."),
     *             @OA\Property(property="password", type="string", example="newpassword123"),
     *             @OA\Property(property="password_confirmation", type="string", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Đặt lại mật khẩu thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đặt lại mật khẩu thành công.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token không hợp lệ hoặc đã hết hạn",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Token không hợp lệ hoặc đã hết hạn.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation lỗi",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    // ========================
    // ĐẶT LẠI MẬT KHẨU MỚI
    // ========================
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'email.exists' => 'Email không tồn tại.',
            'password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        // Kiểm tra token có hợp lệ không
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', hash('sha256', $request->token))
            ->first();
        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn.'
            ], 400);
        }
        // Kiểm tra hết hạn 60 phút
        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Token đã hết hạn, vui lòng yêu cầu lại.'
            ], 400);
        }
        // Cập nhật mật khẩu mới
        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password)
        ]);
        // Xóa token sau khi dùng
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        return response()->json([
            'success' => true,
            'message' => 'Đặt lại mật khẩu thành công.'
        ]);
    }
}
