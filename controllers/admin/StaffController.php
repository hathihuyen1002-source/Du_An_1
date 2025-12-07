<?php
require_once "./models/admin/StaffModel.php";
require_once "./models/admin/UserModel.php";

class StaffController
{
    private $staffModel;
    private $userModel;

    public function __construct()
    {
        require_once "./commons/function.php";
        $this->staffModel = new StaffModel();
        $this->userModel = new UserModel();
    }

    // ============ DANH SÁCH STAFF ============
    public function index($act = null)
    {
        $pageTitle = "Quản lý Hướng dẫn viên";
        $currentAct = $act;

        $keyword = trim($_GET['keyword'] ?? '');
        $staff_type = trim($_GET['staff_type'] ?? '');
        $status = trim($_GET['status'] ?? '');

        $staffs = $this->staffModel->search($keyword, $staff_type, $status);

        $view = "./views/admin/Staff/index.php";
        include "./views/layout/adminLayout.php";
    }

    // ============ FORM THÊM MỚI ============
    public function create($act = null)
    {
        $pageTitle = "Thêm Hướng dẫn viên";
        $currentAct = $act;

        $users = $this->userModel->getUsersByRole('HDV');

        if (empty($users)) {
            $_SESSION['error'] = "⚠️ Chưa có user HDV nào! Vui lòng tạo user với role='HDV' trước.";
            header("Location: index.php?act=admin-staff");
            exit;
        }

        $view = "./views/admin/Staff/create.php";
        include "./views/layout/adminLayout.php";
    }

    // ============ LƯU MỚI ============
    public function store()
    {
        error_log("=== STORE DEBUG START ===");
        error_log("POST data: " . print_r($_POST, true));
        error_log("FILES data: " . print_r($_FILES, true));

        $data = $_POST;

        // Validate
        if (empty($data['user_id'])) {
            error_log("Error: user_id is empty");
            $_SESSION['error'] = "❌ Vui lòng chọn tài khoản user!";
            $_SESSION['old_data'] = $data;
            header("Location: index.php?act=admin-staff-create");
            exit;
        }

        if (empty($data['phone'])) {
            error_log("Error: phone is empty");
            $_SESSION['error'] = "❌ Số điện thoại không được để trống!";
            $_SESSION['old_data'] = $data;
            header("Location: index.php?act=admin-staff-create");
            exit;
        }

        if (!preg_match('/^[0-9]{10,11}$/', $data['phone'])) {
            error_log("Error: phone format invalid");
            $_SESSION['error'] = "❌ Số điện thoại không hợp lệ! Phải có 10-11 chữ số.";
            $_SESSION['old_data'] = $data;
            header("Location: index.php?act=admin-staff-create");
            exit;
        }

        if ($this->staffModel->findByPhone($data['phone'])) {
            error_log("Error: phone already exists");
            $_SESSION['error'] = "❌ Số điện thoại đã được sử dụng!";
            $_SESSION['old_data'] = $data;
            header("Location: index.php?act=admin-staff-create");
            exit;
        }

        if ($this->staffModel->isUserAlreadyStaff($data['user_id'])) {
            error_log("Error: user already staff");
            $_SESSION['error'] = "❌ User này đã là nhân viên rồi!";
            $_SESSION['old_data'] = $data;
            header("Location: index.php?act=admin-staff-create");
            exit;
        }

        // Upload ảnh
        $data['profile_image'] = null;

        if (!empty($_FILES['profile_image']['name'])) {
            error_log("Processing image upload...");

            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            $maxSize = 2 * 1024 * 1024;

            if (!in_array($_FILES['profile_image']['type'], $allowedTypes)) {
                error_log("Error: Invalid file type");
                $_SESSION['error'] = "❌ Chỉ chấp nhận file ảnh JPG, PNG, WEBP!";
                $_SESSION['old_data'] = $data;
                header("Location: index.php?act=admin-staff-create");
                exit;
            }

            if ($_FILES['profile_image']['size'] > $maxSize) {
                error_log("Error: File too large");
                $_SESSION['error'] = "❌ Kích thước ảnh tối đa 2MB!";
                $_SESSION['old_data'] = $data;
                header("Location: index.php?act=admin-staff-create");
                exit;
            }

            if (!defined('PATH_ROOT')) {
                error_log("CRITICAL: PATH_ROOT not defined!");
                $_SESSION['error'] = "❌ Lỗi hệ thống: PATH_ROOT chưa được định nghĩa!";
                $_SESSION['old_data'] = $data;
                header("Location: index.php?act=admin-staff-create");
                exit;
            }

            $uploadedPath = uploadFile($_FILES['profile_image'], 'assets/images/staff/');

            if (!$uploadedPath) {
                error_log("Error: Upload failed!");
                $_SESSION['error'] = "❌ Upload ảnh thất bại!";
                $_SESSION['old_data'] = $data;
                header("Location: index.php?act=admin-staff-create");
                exit;
            }

            $data['profile_image'] = $uploadedPath;
            error_log("Image uploaded: " . $uploadedPath);
        }

        // Lưu database
        try {
            $result = $this->staffModel->store($data);

            if ($result) {
                error_log("✅ Store success!");
                $_SESSION['success'] = "✅ Thêm hướng dẫn viên thành công!";
                header("Location: index.php?act=admin-staff");
            } else {
                error_log("❌ Store failed!");
                if (!empty($data['profile_image'])) {
                    deleteFile($data['profile_image']);
                }
                $_SESSION['error'] = "❌ Thêm thất bại!";
                $_SESSION['old_data'] = $data;
                header("Location: index.php?act=admin-staff-create");
            }
        } catch (Exception $e) {
            error_log("Store Exception: " . $e->getMessage());
            if (!empty($data['profile_image'])) {
                deleteFile($data['profile_image']);
            }
            $_SESSION['error'] = "❌ Lỗi: " . $e->getMessage();
            $_SESSION['old_data'] = $data;
            header("Location: index.php?act=admin-staff-create");
        }

        exit;
    }

    // ============ FORM SỬA ============
    public function edit($act = null)
    {
        $id = $_GET['id'] ?? null;

        if (!$id) {
            $_SESSION['error'] = "❌ Không tìm thấy ID nhân viên!";
            header("Location: index.php?act=admin-staff");
            exit;
        }

        $staff = $this->staffModel->find($id);

        if (!$staff) {
            $_SESSION['error'] = "❌ Nhân viên không tồn tại!";
            header("Location: index.php?act=admin-staff");
            exit;
        }

        $users = $this->userModel->getUsersByRole('HDV');

        $pageTitle = "Sửa Hướng dẫn viên: " . $staff['full_name'];
        $currentAct = $act;
        $view = "./views/admin/Staff/edit.php";
        include "./views/layout/adminLayout.php";
    }

    // ============ CẬP NHẬT ============
    public function update()
    {
        $data = $_POST;
        $id = $data['id'] ?? null;

        error_log("=== UPDATE DEBUG ===");
        error_log("POST: " . print_r($_POST, true));
        error_log("FILES: " . print_r($_FILES, true));

        if (!$id) {
            $_SESSION['error'] = "❌ Không tìm thấy ID nhân viên!";
            $_SESSION['old_data'] = $data;
            header("Location: index.php?act=admin-staff");
            exit;
        }

        $oldStaff = $this->staffModel->find($id);
        if (!$oldStaff) {
            $_SESSION['error'] = "❌ Nhân viên không tồn tại!";
            header("Location: index.php?act=admin-staff");
            exit;
        }

        // Validate
        if (empty($data['user_id'])) {
            $_SESSION['error'] = "❌ Vui lòng chọn tài khoản user!";
            $_SESSION['old_data'] = $data;
            header("Location: index.php?act=admin-staff-edit&id={$id}");
            exit;
        }

        if (empty($data['phone'])) {
            $_SESSION['error'] = "❌ Số điện thoại không được để trống!";
            $_SESSION['old_data'] = $data;
            header("Location: index.php?act=admin-staff-edit&id={$id}");
            exit;
        }

        if (!preg_match('/^[0-9]{10,11}$/', $data['phone'])) {
            $_SESSION['error'] = "❌ Số điện thoại không hợp lệ!";
            $_SESSION['old_data'] = $data;
            header("Location: index.php?act=admin-staff-edit&id={$id}");
            exit;
        }

        $existingPhone = $this->staffModel->findByPhone($data['phone'], $id);
        if ($existingPhone) {
            $_SESSION['error'] = "❌ Số điện thoại đã được sử dụng!";
            $_SESSION['old_data'] = $data;
            header("Location: index.php?act=admin-staff-edit&id={$id}");
            exit;
        }

        // Upload ảnh mới
        if (!empty($_FILES['profile_image']['name'])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            $maxSize = 2 * 1024 * 1024;

            if (!in_array($_FILES['profile_image']['type'], $allowedTypes)) {
                $_SESSION['error'] = "❌ Chỉ chấp nhận file ảnh JPG, PNG, WEBP!";
                $_SESSION['old_data'] = $data;
                header("Location: index.php?act=admin-staff-edit&id={$id}");
                exit;
            }

            if ($_FILES['profile_image']['size'] > $maxSize) {
                $_SESSION['error'] = "❌ Kích thước ảnh tối đa 2MB!";
                $_SESSION['old_data'] = $data;
                header("Location: index.php?act=admin-staff-edit&id={$id}");
                exit;
            }

            $newImage = uploadFile($_FILES['profile_image'], 'assets/images/staff/');

            if ($newImage) {
                $data['profile_image'] = $newImage;

                if (!empty($oldStaff['profile_image']) && $oldStaff['profile_image'] !== $newImage) {
                    $oldImagePath = PATH_ROOT . $oldStaff['profile_image'];
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
            } else {
                $_SESSION['error'] = "❌ Upload ảnh thất bại!";
                $_SESSION['old_data'] = $data;
                header("Location: index.php?act=admin-staff-edit&id={$id}");
                exit;
            }
        } else {
            $data['profile_image'] = $oldStaff['profile_image'];
        }

        $data['id'] = $id;

        // Update database
        try {
            $result = $this->staffModel->update($data);

            if ($result) {
                $_SESSION['success'] = "✅ Cập nhật thành công!";
                header("Location: index.php?act=admin-staff");
            } else {
                $_SESSION['error'] = "❌ Cập nhật thất bại!";
                $_SESSION['old_data'] = $data;
                header("Location: index.php?act=admin-staff-edit&id={$id}");
            }
        } catch (Exception $e) {
            error_log("Update Exception: " . $e->getMessage());
            $_SESSION['error'] = "❌ Lỗi: " . $e->getMessage();
            $_SESSION['old_data'] = $data;
            header("Location: index.php?act=admin-staff-edit&id={$id}");
        }

        exit;
    }

    // ============ XÓA ============
    public function delete()
    {
        $id = $_GET['id'] ?? null;

        if (!$id) {
            $_SESSION['error'] = "❌ Không tìm thấy ID nhân viên!";
            header("Location: index.php?act=admin-staff");
            exit;
        }

        $staff = $this->staffModel->find($id);

        if (!$staff) {
            $_SESSION['error'] = "❌ Nhân viên không tồn tại!";
            header("Location: index.php?act=admin-staff");
            exit;
        }

        if ($this->staffModel->delete($id)) {
            $_SESSION['success'] = "✅ Đã xóa: " . $staff['full_name'];
        } else {
            $_SESSION['error'] = "❌ Không thể xóa!";
        }

        header("Location: index.php?act=admin-staff");
        exit;
    }

    // ============ XEM CHI TIẾT ============
    public function detail($act = null)
    {
        $id = $_GET['id'] ?? null;

        if (!$id) {
            $_SESSION['error'] = "❌ Không tìm thấy ID nhân viên!";
            header("Location: index.php?act=admin-staff");
            exit;
        }

        $staff = $this->staffModel->find($id);

        if (!$staff) {
            $_SESSION['error'] = "❌ Nhân viên không tồn tại!";
            header("Location: index.php?act=admin-staff");
            exit;
        }

        $pageTitle = "Chi tiết HDV: " . $staff['full_name'];
        $currentAct = $act;
        $view = "./views/admin/Staff/detail.php";
        include "./views/layout/adminLayout.php";
    }

    // ============ THỐNG KÊ ============
    public function statistics($act = null)
    {
        $pageTitle = "Thống kê Hướng dẫn viên";
        $currentAct = $act;

        $stats = $this->staffModel->getStats();
        $topStaffs = $this->staffModel->getTopRated(10);

        $view = "./views/admin/Staff/statistics.php";
        include "./views/layout/adminLayout.php";
    }
}
?>