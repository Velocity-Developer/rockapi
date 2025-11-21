<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'avatar',
        'hp',
        'alamat',
        'tgl_masuk',
        'id_karyawan',
        'username',
        'telegram_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $appends = [
        'avatar_url',
        'user_roles',
    ];

    //relasi ke karyawan
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    //relasi ke TodoUser (pivot table for todo assignments with journal tracking)
    public function todoUsers()
    {
        return $this->hasMany(TodoUser::class);
    }

    //relasi ke WmProject
    public function wm_project()
    {
        return $this->hasMany(WmProject::class, 'user_id');
    }

    //relasi ke CsMainProjectClientSupport
    public function cs_main_project_client_supports()
    {
        return $this->hasMany(CsMainProjectClientSupport::class, 'user_id');
    }

    //relasi ke webhost_client_support
    public function webhost_client_supports()
    {
        return $this->hasMany(WebhostClientSupport::class, 'user_id');
    }

    //permissions
    public function get_permissions()
    {
        return $this->getPermissionNames();
    }

    //accessor untuk roles
    public function getUserRolesAttribute()
    {
        $roles = $this->roles()->get();
        $result = [];
        foreach ($roles as $role) {
            $result[] = $role->name;
        }
        return $result;
    }

    // Accessor untuk avatar URL
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar && $this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        // return asset('assets/images/default-avatar.jpg');
        //samarkan id
        $id = str_replace(['=', '/', '+'], '', base64_encode($this->id));
        //tampilkan avatar dari dicebear
        return 'https://api.dicebear.com/9.x/bottts-neutral/svg?seed=' . $id . '9v0';
    }

    //boot
    protected static function boot()
    {
        parent::boot();

        //jika create, dan username kosong, maka generate username dari name
        static::creating(function ($model) {
            if (empty($model->username)) {
                $model->username = Str::replace('-', '_', Str::slug($model->name));
            }
        });
    }
}
