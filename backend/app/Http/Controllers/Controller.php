<?php

namespace App\Http\Controllers;


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
 * @OA\Server(
 *     url="/api",
 *     description="API Server (Tự động nhận diện Host)"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Nhập JWT token vào đây để thực hiện các yêu cầu có bảo mật."
 * )
 */
abstract class Controller
{
    //
}
