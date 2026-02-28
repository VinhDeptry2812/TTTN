<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->get();
        return response()->json($products); 
    }

    public function index_json()
    {
        $products = Product::with('category')->get();
        return view('products',compact('products')); 
    }
}
