<?php

namespace App\Http\Controllers;

use App\Models\Kota;
use Illuminate\Http\Request;

class MitraController extends Controller
{
    public function index()
    {
        $kotas = Kota::orderBy('nama')->get();
        return view('mitra.index', compact('kotas'));
    }
}
