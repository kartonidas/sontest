<?php

namespace App\Services;

use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ArchiveMessages
{
    const BACKUP_DIRECTORY = "sql-backups";

    private string $table;

    private array $columns;

    public function __construct()
    {
        $this->table = app(Message::class)->getTable();

        $this->columns = collect(Message::first())->keys()->map(function($column) {
            return "`$column`";
        })->toArray();
    }

    public function handle(): void
    {
        $this->dumpSql();

        $this->removeOldBackupFiles();
    }

    private function dumpSql()
    {
        $firstRowDate = $lastRowDate = null;

        $tmpName = tempnam("/tmp", "SQL");
        $tempSqlFile = fopen($tmpName, "w");

        $removeAfterDays = config('services.backup.dump_message_after_days');
        if(!Message::where("created_at", "<=", Carbon::now()->subDay($removeAfterDays))->count()) {
            return false;
        }

        DB::beginTransaction();
        foreach (DB::table("messages")
                     ->where("created_at", "<=", Carbon::now()->subDay($removeAfterDays))
                     ->orderBy('created_at', 'ASC')
                     ->cursor()
                     ->chunk(10) as $messages) {

            fwrite($tempSqlFile, "INSERT INTO `$this->table` (" . implode(", ", $this->columns) . ") VALUES \n");

            $it = 0;
            $ids = [];
            foreach($messages as $message) {
                $it++;

                $ids[] = $message->id;

                if($firstRowDate === null) {
                    $firstRowDate = Carbon::createFromDate($message->created_at);
                }

                $values = $this->prepareValues(get_object_vars($message));

                fwrite($tempSqlFile, "(" . implode(", ", $values) . ")");
                fwrite($tempSqlFile, $it == count($messages) ? ";\n" : ",\n");

                $lastRowDate = Carbon::createFromDate($message->created_at);
            }

            Message::whereIn('id', $ids)->delete();
        }
        DB::commit();

        fclose($tempSqlFile);

        if (! Storage::exists(self::BACKUP_DIRECTORY)) {
            Storage::makeDirectory(self::BACKUP_DIRECTORY);
        }

        $name = $firstRowDate->format('YmdHis') . '-' . $lastRowDate->format('YmdHis');
        $zipFileName = self::BACKUP_DIRECTORY . '/' . $name . '.zip';

        $zip = new ZipArchive;
        if ($zip->open(Storage::path($zipFileName), ZipArchive::CREATE) === TRUE) {
            $zip->addFile($tmpName, $name . ".sql");
            $zip->close();
        }

        unlink($tmpName);
    }

    private function removeOldBackupFiles()
    {
        $removeAfterMonths = config('services.backup.remove_backup_after_months');

        $removeOlderThan = Carbon::now()->subMonths($removeAfterMonths);

        $files = Storage::allFiles(self::BACKUP_DIRECTORY);

        foreach($files as $file) {
            $time = Storage::lastModified($file);
            if(Carbon::createFromTimestamp($time)->isBefore($removeOlderThan)) {
                Storage::delete($file);
            }
        }
    }

    private function prepareValues(array $message): array
    {
        return array_map(function ($value) {
            if ($value === null) {
                return "NULL";
            }

            if (is_numeric($value)) {
                return $value;
            }

            if (is_object($value)) {
                $value = json_encode($value);
            }

            return "'" . addslashes($value) . "'";
        }, array_values($message));
    }
}
