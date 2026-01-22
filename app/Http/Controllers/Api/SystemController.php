<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;

class SystemController extends Controller
{
    public function exportSql(Request $request)
    {
        $confirmPassword = $request->input('confirm_password');
        $adminUser = auth()->user();

        if (!Hash::check($confirmPassword, $adminUser->password)) {
            return response()->json(['status' => 401, 'message' => 'Mật khẩu xác nhận không chính xác!'], 401);
        }

        try {
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');
            $dbHost = config('database.connections.mysql.host');

            // 1. Tạo thư mục temp trong storage nếu chưa có
            $dirPath = storage_path("app/public/sql_temp");
            if (!File::exists($dirPath)) {
                File::makeDirectory($dirPath, 0777, true);
            }

            $fileName = "dump.sql";
            $filePath = $dirPath . DIRECTORY_SEPARATOR . $fileName;
            $passwordPart = $dbPass ? "-p\"{$dbPass}\"" : "";

            // 2. Tìm đường dẫn mysqldump (Quét tự động trong Laragon)
            $mysqldumpPath = 'mysqldump';
            $baseDir = 'C:\laragon\bin\mysql';

            if (is_dir($baseDir)) {
                $versions = array_diff(scandir($baseDir), array('..', '.'));
                foreach ($versions as $version) {
                    $testPath = $baseDir . DIRECTORY_SEPARATOR . $version . '\bin\mysqldump.exe';
                    if (file_exists($testPath)) {
                        $mysqldumpPath = "\"$testPath\"";
                        break;
                    }
                }
            }

            // 3. Thực thi lệnh (Dùng dấu ngoặc kép bọc toàn bộ đường dẫn file)
            $command = "{$mysqldumpPath} -u {$dbUser} {$passwordPart} -h {$dbHost} {$dbName} > \"{$filePath}\" 2>&1";

            exec($command);

            if (file_exists($filePath) && filesize($filePath) > 0) {
                $content = file_get_contents($filePath);
                unlink($filePath);

                return response()->json([
                    'status' => 200,
                    'sql_content' => $content
                ]);
            }

            $errorMsg = file_exists($filePath) ? file_get_contents($filePath) : "Không tìm thấy file mysqldump.exe hoặc thư mục ghi file.";
            if (file_exists($filePath))
                unlink($filePath);

            return response()->json([
                'status' => 500,
                'message' => 'Lỗi: ' . $errorMsg
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()]);
        }
    }
}
