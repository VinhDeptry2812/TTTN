<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đang xử lý đăng nhập...</title>
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: #f4f7f6;
        }

        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<div class="loader"></div>
<div id="status" style="margin-top: 20px; font-size: 14px; color: #666;">Đang đồng bộ dữ liệu...</div>

<script>
    const data = {
        token: "{{ $token ?? '' }}",
        user: {!! isset($user) ? json_encode($user) : 'null' !!},
        error: "{{ $error ?? '' }}",
        timestamp: new Date().getTime()
    };

    function sendData() {
        // 1. Luôn ghi vào localStorage như một phương án dự phòng (fallback)
        try {
            localStorage.setItem('facebook_auth_result', JSON.stringify(data));
        } catch (e) {
            console.error("LocalStorage failed:", e);
        }

        // 2. Thử dùng postMessage (Ưu tiên)
        let messageSent = false;
        if (window.opener && !window.opener.closed) {
            try {
                window.opener.postMessage(data, "*");
                messageSent = true;
            } catch (e) {
                console.error("PostMessage failed:", e);
            }
        }

        // 3. Đóng popup sau một khoảng thời gian ngắn
        document.getElementById('status').innerText = messageSent
            ? "Đăng nhập thành công! Đang quay lại trang chính..."
            : "Đang bộ hóa dữ liệu... vui lòng đợi.";

        setTimeout(() => {
            window.close();
            // Nếu sau 1s không tự đóng (do trình duyệt chặn), thì mới redirect
            setTimeout(() => {
                if (!window.closed) {
                    fallbackRedirect();
                }
            }, 1000);
        }, 500);
    }

    function fallbackRedirect() {
        const token = data.token;
        const frontendUrl = "{{ config('app.frontend_url') }}";
        if (token) {
            window.location.href = frontendUrl + "/auth?token=" + token;
        } else {
            window.location.href = frontendUrl + "/auth?error=facebook_failed";
        }
    }

    sendData();
</script>

</html>