<?php

namespace App\Http\Resources\Play;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Enums\ActivityType;
use App\Http\Resources\UserResource;
use App\Http\Resources\Play\PollResource;
use App\Http\Resources\Play\PostResource;
use App\Http\Resources\Play\ShareResource;
use App\Http\Resources\Earn\DonateResource;
use App\Http\Resources\Shared\SupportResource;
use App\Http\Resources\Learn\Academy\AcademyPostResource;
use App\Http\Resources\Learn\Course\posts\CoursePostResource;
use App\Http\Resources\Earn\DonateRecipientResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {       
        // Parse activity_details JSON if exists
        $activityDetails = null;
        if ($this->activity_details) {
            $activityDetails = is_string($this->activity_details) 
                ? json_decode($this->activity_details, true) 
                : $this->activity_details;
        }

        // 'resource' => new PostResource($this->activityable),
        return [
            'id'                            => $this->id,
            'action'                        => ActivityType::normalize($this->activity_type),
            'action_by'                     => new UserResource($this->whenLoaded('user')),
            'action_to'                     => Str::of($this->activityable_type)->substr(11),
            'action_to_id'                  => $this->activityable_id,
            'target_resource'               => $this->relateResource(class_basename($this->activityable_type), $this->whenLoaded('activityable')),
            'activity_details'              => $activityDetails,
            'description'                   => $activityDetails['description'] ?? null,
            'diff_humans_created_at'        => $this->created_at->diffForHumans(),
            'created_at'                    => $this->created_at,
            'updated_at'                    => $this->updated_at,
            // 'class_name'                    => class_basename($this->activityable_type),
        ];
    }

    public function relateResource($type, $model)
    {
        if(!$model) {
            return null;
        }
        
        if($type === 'Post'){
            return new PostResource($model);
        } elseif ($type === 'AcademyPost') {
            return new AcademyPostResource($model);
        } elseif ($type === 'CoursePost') {
            return new CoursePostResource($model);
        } elseif ($type === 'Share') {
            return new ShareResource($model);
        }
        elseif ($type === 'Donate') {
            return new DonateResource($model);
        }
        elseif ($type === 'Support') {
            return new SupportResource($model);
        }
        elseif ($type === 'DonateRecipient') {
            return new DonateRecipientResource($model);
        }
        elseif ($type === 'Poll') {
            return new PollResource($model);
        }
        elseif ($type === 'SupportViewer') {
            // SupportViewer doesn't have a specific resource, return basic data
            return [
                'id' => $model->id,
                'support_id' => $model->support_id,
                'user_id' => $model->user_id,
                'created_at' => $model->created_at,
                'updated_at' => $model->updated_at,
            ];
        }
        
        return null;
    }
}
