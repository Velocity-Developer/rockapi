<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'content',
        'slug',
        'status',
        'author_id',
        'date',
        'featured_image',
    ];

    protected $appends = ['featured_image_url', 'author_data'];

    //relasi author
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // Accessor untuk author_data
    public function getAuthorDataAttribute()
    {
        return $this->author;
    }

    // Accessor untuk featured_image_url
    public function getFeaturedImageUrlAttribute()
    {
        if ($this->featured_image) {
            return asset('storage/' . $this->featured_image);
        }
        return asset('assets/images/default-featured_image.jpg');
    }

    //boot
    public static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            $post->slug = Str::slug($post->title) . '-' . Str::random(5);
        });

        static::updating(function ($post) {
            //jika title berubah
            if ($post->isDirty('title')) {
                $post->slug = Str::slug($post->title) . '-' . Str::random(5);
            }
        });
    }
}
