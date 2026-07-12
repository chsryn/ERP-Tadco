<?php

namespace App\Http\Controllers;

use App\Imports\TadcoMasterImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function index()
    {
        return view('imports.index');
    }

    public function importMaster(Request $request)
    {
        $request->validate([
            'file_master' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new TadcoMasterImport, $request->file('file_master'));

        return back()->with('success', 'Data customer dan item berhasil diimport.');
    }
}
