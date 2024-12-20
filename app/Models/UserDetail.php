<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'phone',
        'phone_2',
        'profile_picture_url',
        'bio',
        'live_in',
        'contact_email',
        'languages',
        'website',
        'skype',
        'facebook',
        'twitter',
        'linkedin',
        'youtube',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'languages' => 'array', // Cast languages as an array
    ];

    /**
     * Relationship with the User model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include users with a specific language.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $language
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithLanguage($query, $language)
    {
        return $query->whereJsonContains('languages', $language);
    }

    /**
     * Scope a query to only include users with a website.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithWebsite($query)
    {
        return $query->whereNotNull('website');
    }

    /**
     * Get the user's full social media links.
     *
     * @return array
     */
    public function getSocialLinksAttribute()
    {
        return [
            'facebook' => $this->facebook,
            'twitter' => $this->twitter,
            'linkedin' => $this->linkedin,
            'youtube' => $this->youtube,
        ];
    }
}
