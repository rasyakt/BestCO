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
        $tab = $request->query('tab', 'bku');
        $startDate = $request->query('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->query('end_date', now()->format('Y-m-d'));

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
}
