<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessExcelFile;
use Illuminate\Support\Facades\Redis;


use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Models\Row;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ExcelUploadController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.basic');
    }

    public function showUploadForm()
    {
        return view('upload');
    }

   

    public function handleUpload(Request $request)
    {
        ini_set('max_execution_time', '300');

        $request->validate([
            'file' => 'required|file|mimes:xlsx',
        ]);

        $filePath = $request->file('file')->store('uploads');
        $redisKey = 'import_progress_' . uniqid();
        Redis::set($redisKey, 0);

        ProcessExcelFile::dispatch($filePath, $redisKey);

        return response()->json(
            ['message' => 'Файл загружен и отправлен в очередь'],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }
}
