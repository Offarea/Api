<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Resources\Api\v1\VendorResource;
use App\Models\v1\Vendor;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VendorController extends Controller
{
    public function all(Request $request)
    {
        return \response(
            VendorResource::collection(Vendor::all())
        );
    }
}
