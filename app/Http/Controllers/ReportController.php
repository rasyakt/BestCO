<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Accounting\FinancialStatementGenerator;
use App\Services\Setting\AppIdentityService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class ReportController extends Controller
{
    /**
     * Daftar tab laporan yang didukung halaman maupun ekspor dokumen.
     *
     * @var array<int, string>
     */
    private const ALLOWED_TABS = ['bku', 'journal', 'trial_balance', 'income_statement', 'balance_sheet'];

    public function __construct(
        private readonly FinancialStatementGenerator $statementGenerator
    ) {}

    public function index(Request $request): View
    {
        [$tab, $startDate, $endDate] = $this->resolveFilters($request);
        $data = $this->reportData($tab, $startDate, $endDate);

        if ($tab === 'bku' || $tab === 'journal') {
            $data['entries'] = $this->paginateRows($data['entries'], $request);
        } elseif ($tab === 'trial_balance') {
            $data['rows'] = $this->paginateRows($data['rows'], $request);
        }

        return view('reports.index', compact('tab', 'startDate', 'endDate', 'data'));
    }

    /**
     * Unduh laporan sebagai dokumen PDF A4 (bukan tangkapan layar).
     * Seluruh baris diekspor, bukan hanya halaman yang sedang tampil.
     */
    public function export(Request $request): Response
    {
        [$tab, $startDate, $endDate] = $this->resolveFilters($request);
        $data = $this->reportData($tab, $startDate, $endDate);

        $identity = AppIdentityService::get();
        $store = StoreSettingController::getStoreInfo();
        $user = $request->user();

        $logoPath = public_path('img/best.png');
        if ($identity['has_custom_logo'] && ! empty($identity['app_logo'])) {
            $customLogo = storage_path('app/public/' . $identity['app_logo']);

            if (is_file($customLogo)) {
                $logoPath = $customLogo;
            }
        }

        $html = view('reports.pdf', [
            'tab' => $tab,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'data' => $data,
            'identity' => $identity,
            'store' => $store,
            'logoPath' => str_replace('\\', '/', $logoPath),
            'printedAt' => now()->timezone('Asia/Jakarta'),
            'printedBy' => $user?->name,
            'printedByNip' => $user?->nip_nisn,
        ])->render();

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', true);
        $options->set('chroot', base_path());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf(
                'attachment; filename="%s"',
                $this->exportFilename($tab, $startDate, $endDate)
            ),
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Validasi tab dan rentang tanggal yang dipakai halaman maupun ekspor.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private function resolveFilters(Request $request): array
    {
        $tab = (string) $request->query('tab', 'bku');

        if (! in_array($tab, self::ALLOWED_TABS, true)) {
            $tab = 'bku';
        }

        $startDate = $this->validDate($request->query('start_date'))
            ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->validDate($request->query('end_date'))
            ?? now()->format('Y-m-d');

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$tab, $startDate, $endDate];
    }

    /**
     * Ambil data laporan tanpa pagination agar ekspor memuat seluruh baris.
     *
     * @return array<string, mixed>
     */
    private function reportData(string $tab, string $startDate, string $endDate): array
    {
        return match ($tab) {
            'bku' => ['entries' => $this->statementGenerator->getCashBook($startDate, $endDate)],
            'journal' => ['entries' => $this->statementGenerator->getGeneralJournal($startDate, $endDate)],
            'trial_balance' => $this->statementGenerator->getTrialBalance($startDate, $endDate),
            'income_statement' => $this->statementGenerator->getIncomeStatement($startDate, $endDate),
            'balance_sheet' => $this->statementGenerator->getBalanceSheet($endDate),
            default => ['entries' => $this->statementGenerator->getCashBook($startDate, $endDate)],
        };
    }

    /**
     * Nama berkas dokumen mengikuti jenis laporan dan periodenya.
     */
    private function exportFilename(string $tab, string $startDate, string $endDate): string
    {
        $labels = [
            'bku' => 'Buku-Kas',
            'journal' => 'Jurnal-Umum',
            'trial_balance' => 'Neraca-Saldo',
            'income_statement' => 'Laba-Rugi',
            'balance_sheet' => 'Neraca',
        ];

        $period = $tab === 'balance_sheet'
            ? 'per-' . $endDate
            : $startDate . '_sd_' . $endDate;

        return sprintf('BESTCO-%s-%s.pdf', $labels[$tab] ?? 'Laporan', $period);
    }

    /**
     * Bagi koleksi/array baris laporan menjadi halaman agar tabel panjang
     * tetap ringkas tanpa mengubah total perhitungan laporan.
     */
    private function paginateRows(Collection|array $rows, Request $request, int $perPage = 10): LengthAwarePaginator
    {
        $items = $rows instanceof Collection ? $rows->values() : collect($rows)->values();
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
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
