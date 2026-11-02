<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StockMutation;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class StockMutationController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('q', '');

        // 1. Statistik Mutasi Internal
        $totalMutations = StockMutation::count();
        $totalMutationValue = StockMutation::sum('total_mutation_value');
        $totalSheetsConverted = StockMutation::sum('target_qty_added');

        // 2. Query Detail Mutasi
        $query = StockMutation::with([
            'posShift',
            'sourceProduct.unit',
            'targetProduct.unit',
            'requestedBy',
            'approvedBy',
            'journalEntry',
        ])->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('mutation_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $mutations = $query->paginate(10)->withQueryString();

        return view('inventory.mutations', compact(
            'mutations',
            'totalMutations',
            'totalMutationValue',
            'totalSheetsConverted',
            'search'
        ));
    }
}

