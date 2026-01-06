<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        if (in_array($sortBy, ['id', 'name', 'email', 'created_at', 'reputation_score', 'years_of_experience']) && in_array(strtolower($sortDir), ['asc', 'desc'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        $limit = $request->input('limit', 10);
        $users = $query->paginate($limit);

        return response()->json([
            'status' => 200,
            'message' => 'Lấy danh sách thành công',
            'data' => $users
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|min:6',
            'role' => 'required|in:reader,author,moderator,admin,banned',
            'bio' => 'nullable|string',
            'years_of_experience' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Lỗi dữ liệu đầu vào',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['password'] = Hash::make($request->password);

            // Đảm bảo các trường mới luôn có giá trị mặc định nếu null
            $data['years_of_experience'] = $request->input('years_of_experience', 0);
            $data['phone'] = $request->input('phone', '');

            $user = User::create($data);

            return response()->json([
                'status' => 201,
                'message' => 'Tạo người dùng thành công',
                'data' => $user
            ], 201);

        } catch (\Throwable $th) {
            Log::error("Lỗi Store User: " . $th->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Lỗi máy chủ nội bộ',
                'debug_error' => $th->getMessage() // Trả về lỗi để bạn check nhanh
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['status' => 404, 'message' => 'Không tìm thấy người dùng'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'role' => 'sometimes|required|in:reader,author,moderator,admin,banned',
            'password' => 'nullable|min:6',
            'bio' => 'nullable|string',
            'years_of_experience' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Lỗi dữ liệu đầu vào',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();

            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $user->update($data);

            return response()->json([
                'status' => 200,
                'message' => 'Cập nhật thành công',
                'data' => $user
            ], 200);

        } catch (\Throwable $th) {
            Log::error("Lỗi Update User: " . $th->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Lỗi máy chủ nội bộ',
                'debug_error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['status' => 404, 'message' => 'Không tìm thấy người dùng'], 404);
        }
        try {
            $user->delete();
            return response()->json(['status' => 200, 'message' => 'Xóa thành công'], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'message' => 'Không thể xóa người dùng'], 500);
        }
    }

    public function resetPassword(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['status' => 404, 'message' => 'Không tìm thấy người dùng'], 404);
        }
        try {
            $user->update(['password' => Hash::make($request->input('password', 'author@123'))]);
            return response()->json(['status' => 200, 'message' => 'Cấp lại mật khẩu thành công'], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'message' => 'Lỗi máy chủ'], 500);
        }
    }

    public function toggleStatus(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['status' => 404, 'message' => 'Không tìm thấy người dùng'], 404);
        }
        try {
            $user->update(['role' => $request->status]);
            return response()->json(['status' => 200, 'message' => 'Cập nhật trạng thái thành công'], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'message' => 'Lỗi máy chủ'], 500);
        }
    }

    public function importExcel(Request $request)
    {
        $usersData = $request->input('users');

        if (!is_array($usersData) || count($usersData) == 0) {
            return response()->json(['status' => 400, 'message' => 'Dữ liệu không hợp lệ'], 400);
        }

        $successCount = 0;
        $errors = [];

        foreach ($usersData as $index => $item) {
            $data = [
                'name' => $item['name'] ?? null,
                'email' => $item['email'] ?? null,
                'phone' => (string) ($item['phone'] ?? ''),
                'location' => $item['location'] ?? null,
                'role' => $item['role'] ?? 'author',
                'years_of_experience' => (int) ($item['years_of_experience'] ?? 0),
                'password' => Hash::make('author@123'),
            ];

            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'role' => 'required|in:reader,author,moderator,admin,banned',
                'years_of_experience' => 'nullable|integer|min:0'
            ]);

            if ($validator->fails()) {
                $errors[] = "Dòng " . ($index + 2) . " ({$data['email']}): " . implode(', ', $validator->errors()->all());
                continue;
            }

            try {
                User::create($data);
                $successCount++;
            } catch (\Throwable $th) {
                $errors[] = "Dòng " . ($index + 2) . ": Lỗi hệ thống khi lưu (" . $th->getMessage() . ")";
            }
        }

        return response()->json([
            'status' => 200,
            'message' => "Import thành công $successCount người dùng.",
            'errors' => $errors
        ], 200);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'reader',
        ]);

        return response()->json(['message' => 'Đăng ký thành công'], 201);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['status' => 401, 'message' => 'Chưa đăng nhập'], 401);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 400,
                'message' => 'Mật khẩu hiện tại không chính xác'
            ], 400);
        }

        try {
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Đổi mật khẩu thành công'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 500,
                'message' => 'Lỗi hệ thống không thể cập nhật mật khẩu'
            ], 500);
        }
    }

    public function toggleRole(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user)
            return response()->json(['status' => 404, 'message' => 'Không tìm thấy'], 404);

        $validator = Validator::make($request->all(), [
            // Đảm bảo có 'banned' trong danh sách validation
            'role' => 'required|in:reader,author,moderator,admin,banned',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        try {
            $user->update(['role' => $request->role]);
            return response()->json(['status' => 200, 'message' => 'Cập nhật thành công']);
        } catch (\Throwable $th) {
            // Gửi kèm lỗi chi tiết để debug
            return response()->json([
                'status' => 500,
                'message' => 'Lỗi hệ thống không thể cập nhật',
                'debug' => $th->getMessage()
            ], 500);
        }
    }
}
