<?php

namespace App\Http\Resources\Api\v1;

use App\Models\v1\UsersMeta;
use Illuminate\Http\Resources\Json\Resource;

class VendorResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        if($this->getUserLevel($this->ID) == 0)
        {
            return [
                'vendor_id' => $this->ID,
                'vendor_name' => $this->getFullName($this->ID)
            ];
        }

    }

    public function getFullName($id)
    {
        $first_name = UsersMeta::where('user_id', $id)
            ->where('meta_key', 'first_name')->first()->meta_value;

        $last_name = UsersMeta::where('user_id', $id)
            ->where('meta_key', 'last_name')->first()->meta_value;

        return $first_name. ' '. $last_name;
    }

    public function getUserLevel($id)
    {
        return UsersMeta::where('user_id', $id)
            ->where('meta_key', 'wp_user_level')->first()->meta_value;
    }
}
