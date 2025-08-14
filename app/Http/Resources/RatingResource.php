<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'feedback' => $this->feedback,
            'session_id' => $this->session_id,
            'ip_address' => $this->when($request->user()?->isAdmin(), $this->ip_address),
            'user' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
            'submitted_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
