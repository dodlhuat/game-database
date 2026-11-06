<?php

namespace App\Http\Controllers\Admin;

use App\Exports\GameExport;
use App\Http\Controllers\Controller;
use App\Imports\GameImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GameImportExportController extends Controller
{
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $import = new GameImport;
        Excel::import($import, $request->file('file'));

        return response()->json([
            /** @var int */
            'new' => $import->newCount,
            /** @var int */
            'updated' => $import->updatedCount,
            /** @var int */
            'total' => $import->newCount + $import->updatedCount,
        ]);
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new GameExport, 'spiele.xlsx');
    }
}
