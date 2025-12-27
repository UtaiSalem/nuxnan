<?php

namespace App\Http\Resources\Play;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostLinkPreviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'url'           => $this->url,
            'domain'        => $this->domain,
            'title'         => $this->title,
            'description'   => $this->description,
            'image_url'     => $this->image_url,
            'site_name'     => $this->site_name,
            'site_icon'     => $this->site_icon,
            'type'          => $this->type,
            'is_video'      => $this->is_video,
            'is_article'    => $this->is_article,
            'video'         => $this->when($this->video_url, function () {
                return [
                    'url'       => $this->video_url,
                    'type'      => $this->video_type,
                    'width'     => $this->video_width,
                    'height'    => $this->video_height,
                ];
            }),
            'author'        => $this->when($this->author_name, function () {
                return [
                    'name'  => $this->author_name,
                    'url'   => $this->author_url,
                ];
            }),
            'provider'      => $this->when($this->provider_name, function () {
                return [
                    'name'  => $this->provider_name,
                    'url'   => $this->provider_url,
                ];
            }),
            'metadata'      => $this->metadata,
            'created_at'    => $this->created_at,
        ];
    }
}
