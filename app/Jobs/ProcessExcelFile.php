<?
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Redis;
use App\Models\Row;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ProcessExcelFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $redisKey;

    public function __construct($filePath, $redisKey)
    {
        $this->filePath = $filePath;
        $this->redisKey = $redisKey;
    }

    public function handle()
    {
        ini_set('max_execution_time', '300');

        
        $rows = Excel::toArray([], Storage::path( $this->filePath))[0];
        array_shift($rows); 

        $errors = [];
        $processedRows = 0;
        foreach (array_chunk($rows, 1000) as $chunkIndex => $chunk) {
            $chunkedData =[];
            foreach ($chunk as $index => $row) {
                $lineNumber = $chunkIndex * 1000 + $index + 2; 
                [$id, $name, $date] = $row;

                $errorMessages = [];


                $mappedRow = [
                    'id' => $row[0] ?? null,
                    'name' => $row[1] ?? null,
                    'date' => $row[2] ?? null,
                ];
                $validator = validator($mappedRow, [
                    'id' => 'required|numeric|gt:0|unique:rows,id',
                    'name' => 'required|string',
                    'date' => 'required|date_format:d.m.Y'
                ]);
        
                if ($validator->fails()) {
                    $errorMessages[] = "{$lineNumber} - " . implode(", ", $validator->errors()->all());
                } 
                


                if (!empty($errorMessages)) {
                    $errors[] = "$lineNumber - " . implode(", ", $errorMessages);
                    continue;
                }
                $chunkedData []=[
                    'id' => $id, 
                    'name' => $name, 
                    'date' => Carbon::createFromFormat('d.m.Y', $date)->toDateString()

                ];
                $processedRows++;
            }
            if (count($chunkedData )) {
                Row::insert($chunkedData );
                
            }
            Redis::set($this->redisKey, $processedRows);
        }
        if (count($errors)) {
        

            $content = "";
            foreach ($errors as $error) {
                $content .= $error . "\n";
            }
            $directoryPath = base_path('error-files');
            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0755, true);
            }
            $path = $directoryPath . '/result'. $this->redisKey .'.txt';
            File::put($path, $content);
            \Artisan::call('git:push');

        }


    }
}