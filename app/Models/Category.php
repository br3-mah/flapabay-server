<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'icon',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'name' => 'string', // Cast name as a string
        'icon' => 'string', // Cast icon as a string
        'description' => 'string', // Cast description as a string
    ];

    /**
     * Get the category name in uppercase (Optional accessor).
     */
    public function getUppercaseNameAttribute()
    {
        return strtoupper($this->name);
    }

    /**
     * Relationship with Property model (if applicable).
     */
    public function properties()
    {
        return $this->belongsToMany(Property::class, 'category_property', 'category_id', 'property_id');
    }
}
