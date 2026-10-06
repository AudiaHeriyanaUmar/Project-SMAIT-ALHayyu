<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseBackupController extends Controller
{
    public function download(Request $request, AdminAuditLogger $auditLogger): StreamedResponse
    {
        abort_unless(DB::getDriverName() === 'mysql', 501, 'Unduhan backup saat ini hanya mendukung database MySQL.');

        $auditLogger->record($request->user(), 'database.backup_requested', 'Unduhan backup database lengkap diminta oleh admin.');

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                throw new \RuntimeException('File backup tidak dapat dibuat.');
            }

            fwrite($output, "-- SMAIT Al-Hayyu database backup\nSET FOREIGN_KEY_CHECKS=0;\n\n");
            $tables = DB::select(
                'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = ? ORDER BY TABLE_NAME',
                ['BASE TABLE'],
            );
            $pdo = DB::connection()->getPdo();

            foreach ($tables as $tableRow) {
                $table = $tableRow->TABLE_NAME;
                $quotedTable = $this->quoteIdentifier($table);
                $createResult = DB::select("SHOW CREATE TABLE {$quotedTable}");

                if ($createResult === []) {
                    throw new \RuntimeException("Skema tabel {$table} tidak dapat dibaca.");
                }

                $createSql = array_values((array) $createResult[0])[1] ?? null;

                if (! is_string($createSql)) {
                    throw new \RuntimeException("Definisi tabel {$table} tidak tersedia.");
                }

                fwrite($output, "DROP TABLE IF EXISTS {$quotedTable};\n{$createSql};\n");
                $columns = DB::select("SHOW COLUMNS FROM {$quotedTable}");
                $columnNames = array_map(fn ($column) => $this->quoteIdentifier($column->Field), $columns);

                foreach (DB::table($table)->cursor() as $row) {
                    $values = [];

                    foreach ((array) $row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                            continue;
                        }

                        $quoted = $pdo->quote((string) $value);

                        if (! is_string($quoted)) {
                            throw new \RuntimeException("Nilai data pada tabel {$table} tidak dapat di-quote.");
                        }

                        $values[] = $quoted;
                    }

                    fwrite($output, 'INSERT INTO '.$quotedTable.' ('.implode(', ', $columnNames).') VALUES ('.implode(', ', $values).");\n");
                }

                fwrite($output, "\n");
            }

            fwrite($output, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($output);
        }, 'smait-alhayyu-backup-'.now()->format('Ymd-His').'.sql', [
            'Content-Type' => 'application/sql; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }
}
