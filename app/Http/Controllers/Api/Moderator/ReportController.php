<?php

namespace App\Http\Controllers\Api\Moderator;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Events\ReportSubmitted;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    /**
     * Lấy danh sách báo cáo cho Admin/Moderator
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $reports = Report::with(['reporter:id,name,email', 'reportedUser:id,name,email'])
                ->when($request->status && $request->status !== 'all', function ($query) use ($request) {
                    return $query->where('status', $request->status);
                })
                ->latest()
                ->get();

            return response()->json([
                'status' => 200,
                'data' => $reports
            ]);
        } catch (\Exception $e) {
            Log::error("Lỗi lấy danh sách báo cáo: " . $e->getMessage());
            return response()->json(['message' => 'Lỗi server khi lấy dữ liệu'], 500);
        }
    }

    /**
     * Người dùng gửi báo cáo mới
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reported_user_id' => 'required',
                'type' => 'required|string',
                'description' => 'required|string|min:10',
                'evidence' => 'nullable|array',
                'priority' => 'nullable|string'
            ]);

            $report = Report::create([
                'reporter_id' => auth()->id(),
                'reported_user_id' => $validated['reported_user_id'],
                'type' => $validated['type'],
                'description' => $validated['description'],
                'evidence' => $validated['evidence'] ?? null,
                'priority' => $validated['priority'] ?? 'Medium',
                'status' => 'Pending'
            ]);

            // Bắn event Realtime (Bọc trong try-catch để nếu lỗi cấu hình Pusher cũng không sập API)
            try {
                if (class_exists(ReportSubmitted::class)) {
                    broadcast(new ReportSubmitted($report))->toOthers();
                }
            } catch (\Exception $e) {
                Log::warning("Broadcasting error: " . $e->getMessage());
            }

            return response()->json([
                'status' => 201,
                'message' => 'Báo cáo đã được gửi và đang chờ xử lý',
                'data' => $report
            ], 201);

        } catch (\Exception $e) {
            Log::error("Lỗi gửi báo cáo: " . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Cập nhật trạng thái báo cáo (Duyệt/Bác bỏ)
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $report = Report::findOrFail($id);

            $report->update([
                'status' => $request->status,
                'moderator_note' => $request->moderator_note
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Cập nhật trạng thái báo cáo thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Không tìm thấy báo cáo hoặc lỗi server'], 500);
        }
    }

    /**
     * Khóa tài khoản người dùng bị báo cáo
     */
    public function banUser($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            // Cập nhật role hoặc một cột is_banned tùy cấu trúc của bạn
            $user->update(['role' => 'banned']);

            return response()->json([
                'status' => 200,
                'message' => 'Tài khoản ' . $user->name . ' đã bị khóa vĩnh viễn'
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }
}
