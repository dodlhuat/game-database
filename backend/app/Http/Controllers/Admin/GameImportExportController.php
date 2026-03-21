<?php

namespace App\Http\Controllers\Admin;

use App\Exports\GameExport;
use App\Http\Controllers\Controller;
use App\Imports\GameImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class GameImportExportController extends Controller
{
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $import = new GameImport();
        Excel::import($import, $request->file('file'));

        return response()->json([
            'new'     => $import->newCount,
            'updated' => $import->updatedCount,
            'total'   => $import->newCount + $import->updatedCount,
        ]);
    }

    public function export(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return Excel::download(new GameExport(), 'spiele.xlsx');
    }
}
