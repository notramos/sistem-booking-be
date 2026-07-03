<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CongregationServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'service_type' => $this->service_type,
            'applicant_name' => $this->applicant_name,
            'applicant_gender' => $this->applicant_gender,
            'baptismal_name' => $this->baptismal_name,
            'birth_place' => $this->birth_place,
            'birth_date' => $this->birth_date,
            'address' => $this->address,
            'contact' => $this->contact,
            'phone' => $this->phone,
            'mobile_phone' => $this->mobile_phone,
            'neighborhood' => $this->neighborhood,
            'region' => $this->region,
            'parish' => $this->parish,
            'father_name' => $this->father_name,
            'father_religion' => $this->father_religion,
            'mother_name' => $this->mother_name,
            'mother_religion' => $this->mother_religion,
            'school' => $this->school,
            'grade' => $this->grade,
            'occupation' => $this->occupation,
            'family_card_number' => $this->family_card_number,
            'service_date' => $this->service_date,
            'description' => $this->description,
            'status' => $this->status,
            'notes' => $this->notes,
            'dynamic_fields' => $this->dynamic_fields,
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at,
        ];
    }
}
