<?php

namespace App\Http\Controllers;

use App\Models\Kota;

class MitraController extends Controller
{
    public function index()
    {
        $kotas = Kota::orderBy('nama_kota')->get();

        return view('mitra.index', compact('kotas'));
    }
}
