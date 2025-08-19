<?php

namespace Modules\Auth\Transformers\Frontend;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'country_code' => $this->country_code,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'username' => $this->username,
            'birthday' => $this->birthday,
            'gender' => $this->gender,
            'is_active' => $this->is_active,
            'registration_type' => $this->registration_type,
            'has_google_account' => $this->hasGoogleAccount(),
            'avatar' => $this->avatar,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
