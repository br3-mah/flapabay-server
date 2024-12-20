<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyType extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'property_types';

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
     * Relationship with Property model (if applicable).
     */
    public function properties()
    {
        return $this->hasMany(Property::class, 'property_type_id');
    }
}
