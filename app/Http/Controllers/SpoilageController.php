<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SpoilageLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SpoilageController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('q', '');
        $category = $request->query('category', 'ALL');

        // Statistik Kumulatif Spoilage
        $totalIncidents = SpoilageLog::count();
        $totalLossRupiah = SpoilageLog::sum('total_spoilage_cost');
        $totalFailedOutput = SpoilageLog::sum('failed_output_qty');

        $query = SpoilageLog::with([
            'posShift',
            'service',
            'items.product.unit',
            'reportedBy',
            'verifiedBy',
            'journalEntry',
        ])->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('spoilage_number', 'like', "%{$search}%")
                    ->orWhere('reason_description', 'like', "%{$search}%");
            });
        }

        if ($category !== 'ALL') {
            $query->where('reason_category', $category);
        }

        $logs = $query->paginate(15);

        return view('inventory.spoilage', compact(
            'logs',
            'totalIncidents',
            'totalLossRupiah',
            'totalFailedOutput',
            'search',
            'category'
        ));
    }
}

