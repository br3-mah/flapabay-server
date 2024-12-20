<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'properties';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'location',
        'address',
        'county',
        'latitude',
        'longitude',
        'check_in_hour',
        'check_out_hour',
        'num_of_guests',
        'num_of_children',
        'maximum_guests',
        'allow_extra_guests',
        'neighborhood_area',
        'country',
        'show_contact_form_instead_of_booking',
        'allow_instant_booking',
        'currency',
        'price_range',
        'price',
        'price_per_night',
        'additional_guest_price',
        'children_price',
        'amenities',
        'house_rules',
        'page',
        'rating',
        'favorite',
        'images',
        'video_link',
        'verified',
        'property_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'price_range' => 'array', // Cast price_range as an array
        'amenities' => 'array', // Cast amenities as an array
        'house_rules' => 'array', // Cast house_rules as an array
        'images' => 'array', // Cast images as an array
        'video_link' => 'array', // Cast video link as an array
        'price' => 'decimal:2', // Cast price as decimal with 2 decimal places
        'price_per_night' => 'decimal:2', // Cast price per night as decimal with 2 decimal places
        'additional_guest_price' => 'decimal:2', // Cast additional guest price as decimal with 2 decimal places
        'children_price' => 'decimal:2', // Cast children price as decimal with 2 decimal places
        'rating' => 'decimal:2', // Cast rating as decimal with 2 decimal places
        'favorite' => 'boolean', // Cast favorite as a boolean
        'verified' => 'boolean', // Cast verified as a boolean
        'allow_extra_guests' => 'boolean', // Cast allow extra guests as a boolean
        'show_contact_form_instead_of_booking' => 'boolean', // Cast as boolean
        'allow_instant_booking' => 'boolean', // Cast as boolean
    ];

    /**
     * Scope a query to only include verified properties.
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    /**
     * Scope a query to only include favorite properties.
     */
    public function scopeFavorite($query)
    {
        return $query->where('favorite', true);
    }

    /**
     * Scope a query to filter by property type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('property_type', $type);
    }

    /**
     * Scope a query to filter properties within a price range.
     */
    public function scopeWithinPriceRange($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    /**
     * Relationship with Post model (if needed).
     */
    public function listing()
    {
        return $this->hasMany(Listing::class, 'property_id');
    }

    /**
     * Get the formatted price range.
     */
    public function getFormattedPriceRangeAttribute()
    {
        if ($this->price_range) {
            return 'Min: ' . $this->price_range['min'] . ' - Max: ' . $this->price_range['max'];
        }
        return null;
    }

    /**
     * Get the full address as a formatted string.
     */
    public function getFullAddressAttribute()
    {
        return $this->address . ', ' . $this->county . ', ' . $this->country;
    }

    /**
     * Determine if the property allows instant booking.
     */
    public function allowsInstantBooking()
    {
        return $this->allow_instant_booking;
    }

    /**
     * Check if a property is marked as a favorite.
     */
    public function isFavorite()
    {
        return $this->favorite;
    }

    /**
     * Get the URL for the first image (if images are an array).
     */
    public function getFirstImageUrlAttribute()
    {
        return $this->images && count($this->images) > 0 ? $this->images[0] : null;
    }

    /**
     * Get the Google Maps URL for the property location.
     */
    public function getGoogleMapsUrlAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
        }
        return null;
    }
}
