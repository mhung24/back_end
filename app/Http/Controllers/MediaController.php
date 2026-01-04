<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MediaController extends Controller
{
    public function store(Request $request)
    {
        // Validate
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // Đã khớp với React (formData gửi key 'file')
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Lỗi file gửi lên',
                'errors' => $validator->errors()
            ], 200);
        }

        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');

                // 1. Lưu file vào folder storage/app/public/uploads
                // $path sẽ trả về dạng: "uploads/ten-file-random.jpg"
                $path = $file->store('uploads', 'public');

                // 2. Tạo bản ghi Media
                $media = Media::create([
                    'uploader_id' => $request->user()->id,


                    'url' => '/storage/' . $path,

                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'alt_text' => $file->getClientOriginalName(),
                ]);

                return response()->json([
                    'status' => 200,
                    'message' => 'Upload thành công',
                    'data' => $media
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 500,
                'message' => 'Lỗi server: ' . $th->getMessage()
            ], 200);
        }
    }
}
