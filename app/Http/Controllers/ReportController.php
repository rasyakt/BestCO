<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Accounting\FinancialStatementGenerator;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ReportController extends Controller
{
    public function __construct(
        private readonly FinancialStatementGenerator $statementGenerator
    ) {}

    public function index(Request $request): View
    {
        $allowedTabs = ['bku', 'journal', 'trial_balance', 'income_statement', 'balance_sheet'];
        $tab = (string) $request->query('tab', 'bku');

        if (! in_array($tab, $allowedTabs, true)) {
            $tab = 'bku';
        }

        $startDate = $this->validDate($request->query('start_date'))
            ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->validDate($request->query('end_date'))
            ?? now()->format('Y-m-d');

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $data = match ($tab) {
            'bku' => ['entries' => $this->statementGenerator->getCashBook($startDate, $endDate)],
            'journal' => ['entries' => $this->statementGenerator->getGeneralJournal($startDate, $endDate)],
            'trial_balance' => $this->statementGenerator->getTrialBalance($startDate, $endDate),
            'income_statement' => $this->statementGenerator->getIncomeStatement($startDate, $endDate),
            'balance_sheet' => $this->statementGenerator->getBalanceSheet($endDate),
            default => ['entries' => $this->statementGenerator->getCashBook($startDate, $endDate)],
        };

        return view('reports.index', compact('tab', 'startDate', 'endDate', 'data'));
    }

    /**
     * Validasi tanggal query string tanpa melempar exception ke halaman laporan.
     */
    private function validDate(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
}

