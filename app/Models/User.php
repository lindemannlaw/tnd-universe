<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RolesEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, InteractsWithMedia, HasRoles;

    public string $mediaCollection = 'avatars';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
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

    public function registerMediaConversions(Media $media = null): void
    {
        $this
            ->addMediaConversion('avatar')
            ->width(500)
            ->height(500)
            ->nonQueued()
            ->keepOriginalImageFormat()
            ->performOnCollections($this->mediaCollection);
    }

    public function getRolesLabel()
    {
        $roleCases = RolesEnum::cases();

        $roleArray = array_combine(
            array_map(fn($role) => $role->value, $roleCases),
            array_map(fn($role) => $role->label(), $roleCases)
        );

        $rolesLabels = array_filter($roleArray, function ($key) {
            $roleNames = $this->getRoleNames()->toArray();

            return in_array($key, $roleNames);
        }, ARRAY_FILTER_USE_KEY);


        return $rolesLabels;
    }
}
