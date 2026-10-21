<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\CompanyPayment;
use App\Models\Expense;
use App\Models\JournalVoucher;
use App\Support\AttachmentDisk;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Authenticated downloads of the receipts and proofs attached to money records. */
final class AttachmentController extends Controller
{
    public function companyPayment(CompanyPayment $companyPayment): StreamedResponse
    {
        Gate::authorize('view', $companyPayment);

        return $this->send($companyPayment->attachment_path, "payment-{$companyPayment->id}");
    }

    public function expense(Expense $expense): StreamedResponse
    {
        Gate::authorize('view', $expense);

        return $this->send($expense->attachment_path, "expense-{$expense->id}");
    }

    public function journalVoucher(JournalVoucher $journalVoucher): StreamedResponse
    {
        Gate::authorize('view', $journalVoucher);

        return $this->send($journalVoucher->attachment_path, "voucher-{$journalVoucher->id}");
    }

    private function send(?string $path, string $name): StreamedResponse
    {
        abort_if($path === null, 404);

        return AttachmentDisk::download($path, $name.'.'.pathinfo($path, PATHINFO_EXTENSION));
    }
}
