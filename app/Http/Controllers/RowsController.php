<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Row;

class RowsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.basic');
    }

    public function index()
    {
        $rows = Row::orderBy('date', 'desc')->paginate(10);
        return view('rows', compact('rows'));
    }
}
