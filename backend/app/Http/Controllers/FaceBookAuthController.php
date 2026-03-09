<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FaceBookAuthController extends Controller
{
    /**
     * @OA\Get(
     *     path="/auth/facebook",
     *     summary="Chuyển hướng sang trang đăng nhập Facebook",
     *     tags={"Auth"},
     *     @OA\Response(response=302, description="Redirect to Facebook OAuth")
     * )
     */
    public function redirectToFacebook()
    {
        $redirectUrl = config('services.facebook.redirect');
        $driver = Socialite::driver('facebook')
            ->scopes(['public_profile', 'email'])
            ->usingGraphVersion('v19.0')
            ->stateless()
            ->redirectUrl($redirectUrl);

        // Bỏ qua kiểm tra SSL trên môi trường local (Windows)
        if (config('app.env') === 'local') {
            $driver->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
        }

        $url = $driver->redirect()->getTargetUrl();

        return response()
            ->view('auth.redirectfb', ['url' => $url])
            ->header('Content-Type', 'text/html');

        

    }

    /**
     * @OA\Get(
     *     path="/auth/facebook/callback",
     *     summary="Xử lý callback từ Facebook",
     *     tags={"Auth"},
     *     @OA\Response(response=302, description="Redirect về Frontend kèm token hoặc lỗi")
     * )
     */
    public function handleFacebookCallback()
    {
        try {
            $redirectUrl = config('services.facebook.redirect');
            $driver = Socialite::driver('facebook')
                ->stateless()
                ->redirectUrl($redirectUrl);

            // Bỏ qua kiểm tra SSL trên môi trường local (Windows)
            if (config('app.env') === 'local') {
                $driver->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
            }

            $facebookUser = $driver->user();

            // Tìm user theo email (Facebook có thể không trả về email nếu user không cung cấp)
            $email = $facebookUser->getEmail();

            if (!$email) {
                // Nếu không có email, fallback về facebook_id@facebook.com hoặc báo lỗi
                $email = $facebookUser->getId() . '@facebook.com';
            }

            $user = User::where('email', $email)->first();

            if (!$user) {
                // Tạo mới nếu chưa có
                $user = User::create([
                    'name' => $facebookUser->getName() ?? $facebookUser->getNickname() ?? 'Facebook User',
                    'email' => $email,
                    'password' => Hash::make(Str::random(24)),
                    'provider_id' => $facebookUser->getId(),
                    'provider' => 'facebook',   
                    'is_active' => '1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Đăng nhập và tạo token
            /** @var string $token */
            $token = Auth::login($user);

            // Redirect về Frontend kèm Token
            return response()
                ->view('auth.callback', [
                    'token' => $token,
                    'user' => $user
                ])
                ->header('Content-Type', 'text/html');

        } catch (\Exception $e) {
            \Log::error('Facebook Login Error: ' . $e->getMessage());
            return response()
                ->view('auth.callback', [
                    'error' => 'facebook_failed'
                ])
                ->header('Content-Type', 'text/html');
        }



       
    }
}
