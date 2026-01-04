<?php

namespace App\Http\Controllers\Api\Moderator;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use App\Events\ReportSubmitted;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        try {
            $reports = Report::with(['reporter', 'reportedUser'])
                ->when($request->status && $request->status !== 'all', function ($query) use ($request) {
                    return $query->where('status', $request->status);
                })
                ->latest()
                ->get();

            return response()->json(['data' => $reports]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $report = Report::findOrFail($id);
            $report->update([
                'status' => $request->status,
                'moderator_note' => $request->moderator_note
            ]);

            return response()->json(['message' => 'Cập nhật thành công']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function banUser($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->update(['role' => 'banned']);

            return response()->json(['message' => 'Đã khóa tài khoản người dùng']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'reported_user_id' => 'required|exists:users,id',
                'type' => 'required|string',
                'description' => 'required|string',
                'evidence' => 'nullable|array',
                'priority' => 'nullable|string'
            ]);

            $report = Report::create([
                'reporter_id' => auth()->id(),
                'reported_user_id' => $validated['reported_user_id'],
                'type' => $validated['type'],
                'description' => $validated['description'],
                'evidence' => $validated['evidence'],
                'priority' => $validated['priority'] ?? 'Medium',
                'status' => 'Pending'
            ]);

            broadcast(new ReportSubmitted($report))->toOthers();

            return response()->json(['message' => 'Báo cáo đã được gửi'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
