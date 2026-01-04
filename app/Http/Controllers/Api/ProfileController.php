<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json($request->user());
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
        ]);

        $user = $request->user();

        $user->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'location' => $validated['location'] ?? null,
            'website' => $validated['website'] ?? null,
            'bio' => $validated['bio'] ?? null,
        ]);

        return response()->json([
            'message' => 'Cập nhật hồ sơ thành công!',
            'user' => $user
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        try {
            $user = $request->user();

            if ($user->avatar) {
                $oldPath = str_replace(asset('storage/'), '', $user->avatar);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }


            $path = $request->file('file')->store('uploads', 'public');

            $url = Storage::url($path);

            $user->avatar = asset($url);
            $user->save();

            return response()->json([
                'message' => 'Upload ảnh thành công!',
                'data' => [
                    'url' => $user->avatar
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi upload', 'error' => $e->getMessage()], 500);
        }
    }
}
