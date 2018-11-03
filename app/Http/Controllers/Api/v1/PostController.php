<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Resources\Api\v1\ProductSummary;
use App\Models\v1\Products;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PostController extends Controller
{
    public function all_product_summary(Request $request)
    {
        return \response([
            ProductSummary::collection(Products::all()
                ->where('post_type', 'product')
                ->where('post_status', 'publish'))
        ]);

    }
}
