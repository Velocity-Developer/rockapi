<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start',
        'end',
        'status',
        'priority',
        'user_id',
        'role',
        'webhost_id',
        'cs_main_project_id',
        'journal_category_id',
    ];

    // cast
    protected $casts = [
        'user_id' => 'integer',
        'journal_category_id' => 'integer',
    ];

    // relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // relasi ke journal category
    public function journalCategory()
    {
        return $this->belongsTo(JournalCategory::class);
    }

    // relasi ke webhost
    public function webhost()
    {
        return $this->belongsTo(Webhost::class, 'webhost_id', 'id_webhost');
    }

    // relasi ke cs main project
    public function csMainProject()
    {
        return $this->belongsTo(CsMainProject::class, 'cs_main_project_id');
    }

    // relasi ke journal detail support
    public function detail_support()
    {
        return $this->hasOne(JournalDetailSupport::class);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('start', Carbon::now()->month)
            ->whereYear('start', Carbon::now()->year);
    }
}
