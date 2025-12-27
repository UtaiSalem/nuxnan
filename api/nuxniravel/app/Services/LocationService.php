<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostLocation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationService
{
    /**
     * Create or update location for a post.
     */
    public function saveLocation(Post $post, array $locationData): PostLocation
    {
        // Remove existing location
        $post->postLocation()->delete();

        return PostLocation::create([
            'post_id' => $post->id,
            'name' => $locationData['name'],
            'address' => $locationData['address'] ?? null,
            'city' => $locationData['city'] ?? null,
            'state' => $locationData['state'] ?? null,
            'country' => $locationData['country'] ?? null,
            'postal_code' => $locationData['postal_code'] ?? null,
            'latitude' => $locationData['latitude'] ?? null,
            'longitude' => $locationData['longitude'] ?? null,
            'place_id' => $locationData['place_id'] ?? null,
            'place_type' => $locationData['place_type'] ?? null,
            'icon_url' => $locationData['icon_url'] ?? null,
            'metadata' => $locationData['metadata'] ?? null,
        ]);
    }

    /**
     * Update the simple location field on the post.
     */
    public function updateSimpleLocation(Post $post, string $locationName): void
    {
        $post->update(['location' => $locationName]);
    }

    /**
     * Get nearby places based on coordinates.
     * This is a placeholder - you would integrate with Google Places API, etc.
     */
    public function getNearbyPlaces(float $lat, float $lng, int $radius = 1000): array
    {
        // Placeholder for Google Places API integration
        // You would implement this with your actual API key
        
        /*
        $apiKey = config('services.google.places_api_key');
        
        $response = Http::get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
            'location' => "{$lat},{$lng}",
            'radius' => $radius,
            'key' => $apiKey,
        ]);
        
        if ($response->successful()) {
            return $response->json('results');
        }
        */
        
        return [];
    }

    /**
     * Search places by query.
     * This is a placeholder - you would integrate with Google Places API, etc.
     */
    public function searchPlaces(string $query, ?float $lat = null, ?float $lng = null): array
    {
        // Placeholder for Google Places API integration
        
        /*
        $apiKey = config('services.google.places_api_key');
        
        $params = [
            'input' => $query,
            'key' => $apiKey,
        ];
        
        if ($lat && $lng) {
            $params['location'] = "{$lat},{$lng}";
            $params['radius'] = 50000;
        }
        
        $response = Http::get('https://maps.googleapis.com/maps/api/place/autocomplete/json', $params);
        
        if ($response->successful()) {
            return $response->json('predictions');
        }
        */
        
        return [];
    }

    /**
     * Get place details by place ID.
     * This is a placeholder - you would integrate with Google Places API, etc.
     */
    public function getPlaceDetails(string $placeId): ?array
    {
        // Placeholder for Google Places API integration
        
        /*
        $apiKey = config('services.google.places_api_key');
        
        $response = Http::get('https://maps.googleapis.com/maps/api/place/details/json', [
            'place_id' => $placeId,
            'key' => $apiKey,
            'fields' => 'name,formatted_address,geometry,types,icon,address_components',
        ]);
        
        if ($response->successful()) {
            return $response->json('result');
        }
        */
        
        return null;
    }

    /**
     * Remove location from post.
     */
    public function removeLocation(Post $post): bool
    {
        $post->update(['location' => null]);
        return $post->postLocation()->delete() >= 0;
    }

    /**
     * Get posts near a location.
     */
    public function getPostsNearLocation(float $lat, float $lng, float $radiusKm = 10, int $limit = 20): array
    {
        $postIds = PostLocation::near($lat, $lng, $radiusKm)
            ->limit($limit)
            ->pluck('post_id')
            ->toArray();

        return Post::whereIn('id', $postIds)
            ->where('is_published', true)
            ->where('privacy_settings', 3) // Public only
            ->with(['user', 'postLocation', 'postImages'])
            ->get()
            ->toArray();
    }
}
