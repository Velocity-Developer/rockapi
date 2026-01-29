<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'version',
        'github_url',
        'download_url',
        'type',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'theme' => 'Theme',
            'plugin' => 'Plugin',
            'child_theme' => 'Child Theme',
            default => 'Unknown'
        };
    }
}
