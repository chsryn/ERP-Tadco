<?php

namespace App\Http\Controllers;

use App\Imports\TadcoMasterImport;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

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

        try {
            Excel::import(new TadcoMasterImport, $request->file('file_master'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Import gagal: ' . $e->getMessage());
        }

        return back()->with('success', 'Data customer dan item berhasil diimport.');
    }
}
