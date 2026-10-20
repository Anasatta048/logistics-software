<?php

namespace App\Services\Import;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\AccountTransfer;
use App\Models\Broker;
use App\Models\Commission;
use App\Models\CommissionPayout;
use App\Models\CommissionTier;
use App\Models\Company;
use App\Models\CompanyPayment;
use App\Models\CustomField;
use App\Models\Driver;
use App\Models\EquipmentType;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\JournalVoucher;
use App\Models\LedgerEntry;
use App\Models\Load;
use App\Models\Setting;
use App\Models\TaxRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use RuntimeException;
use ZipArchive;

/**
 * Writes the current workspace's data as a ZIP of plain CSV files, one per
 * area, in the table's own column names so it can be re-read by any tool.
 * Runs inside the tenant context, so every query is already that workspace's
 * own; rows stream out in chunks, so a big company does not exhaust memory.
 */
final class BackupExporter
{
    /** file name => model class */
    private const AREAS = [
        'carrier-companies' => Company::class,
        'drivers' => Driver::class,
        'brokers' => Broker::class,
        'loads' => Load::class,
        'invoices' => Invoice::class,
        'invoice-lines' => InvoiceLine::class,
        'payments-received' => CompanyPayment::class,
        'expenses' => Expense::class,
        'expense-categories' => ExpenseCategory::class,
        'accounts' => Account::class,
        'account-ledger' => AccountTransaction::class,
        'account-transfers' => AccountTransfer::class,
        'journal-vouchers' => JournalVoucher::class,
        'commissions' => Commission::class,
        'commission-payouts' => CommissionPayout::class,
        'party-ledger' => LedgerEntry::class,
        'equipment-types' => EquipmentType::class,
        'tax-rules' => TaxRule::class,
        'commission-slabs' => CommissionTier::class,
        'custom-fields' => CustomField::class,
        'users' => User::class,
        'settings' => Setting::class,
    ];

    /** Never leave the system, whatever the table. */
    private const HIDDEN = ['tenant_id', 'password', 'remember_token', 'is_super_admin', 'notification_preferences'];

    /** @return int size of the ZIP in bytes */
    public function build(string $zipPath): int
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'backup-'.uniqid();
        mkdir($dir, 0700, true);

        try {
            $files = [];
            foreach (self::AREAS as $name => $class) {
                $file = "{$dir}/{$name}.csv";
                $this->writeArea($class, $file);
                $files[$name.'.csv'] = $file;
            }

            file_put_contents("{$dir}/README.txt", "Backup of {$this->company()} created ".now()->toDateTimeString().".\nEach file is a CSV (open in Excel). Columns use our internal names; dates are UTC.\nPasswords and internal access flags are never included.\n");
            $files['README.txt'] = "{$dir}/README.txt";

            @mkdir(dirname($zipPath), 0775, true);
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Could not create the backup file.');
            }
            foreach ($files as $entry => $path) {
                $zip->addFile($path, $entry);
            }
            $zip->close();

            return (int) filesize($zipPath);
        } finally {
            foreach (glob("{$dir}/*") ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($dir);
        }
    }

    /** @param class-string<Model> $class */
    private function writeArea(string $class, string $file): void
    {
        $model = new $class();
        $columns = array_values(array_diff(Schema::getColumnListing($model->getTable()), self::HIDDEN));

        $writer = new CsvWriter();
        $writer->openToFile($file);
        $writer->addRow(Row::fromValues($columns));

        $class::query()->toBase()->select($columns)->orderBy($model->getKeyName())
            ->chunkById(1000, function ($rows) use ($writer, $columns): void {
                foreach ($rows as $row) {
                    $writer->addRow(Row::fromValues(array_map(
                        fn (string $c) => $this->value($row->{$c} ?? null),
                        $columns,
                    )));
                }
            }, $model->getKeyName());

        $writer->close();
    }

    private function value(mixed $value): string|int|float|null
    {
        return is_bool($value) ? (int) $value : (is_scalar($value) || $value === null ? $value : json_encode($value));
    }

    private function company(): string
    {
        return (string) (app(\App\Support\Tenancy\TenantContext::class)->tenant()?->name ?? 'your workspace');
    }
}
