<?php

namespace Modules\Auth\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Modules\Auth\Database\Factories\UserFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    // Gender constants
    public const MALE = 1;
    public const FEMALE = 2;
    public const CUSTOM = 3;

    // Registration type constants
    public const REGISTRATION_NORMAL = 'normal';
    public const REGISTRATION_GOOGLE = 'google';

    protected $fillable = [
        'name',
        'email',
        'password',
        'country_code',
        'phone',
        'username',
        'province_id',
        'city_id',
        'email_verified_at',
        'phone_verified_at',
        'last_login_at',
        'last_login_ip',
        'is_active',
        'is_admin',
        'google_id',
        'avatar',
        'google_token_expires_at',
        'registration_type',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'google_token_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'is_active' => 'boolean',
        'is_admin' => 'boolean',
    ];

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Handle null values for unique constraints
        static::creating(function ($user) {
            // اگر email خالی است، آن را null کن
            if (empty($user->email) || $user->email === '') {
                $user->email = null;
            }

            // اگر phone خالی است، آن را null کن
            if (empty($user->phone) || $user->phone === '') {
                $user->phone = null;
            }

            // validation: باید حداقل یکی از email یا phone وجود داشته باشد
            if (is_null($user->email) && is_null($user->phone)) {
                throw new \Exception('User must have either email or phone number');
            }
        });

        static::updating(function ($user) {
            // اگر email خالی است، آن را null کن
            if (empty($user->email) || $user->email === '') {
                $user->email = null;
            }

            // اگر phone خالی است، آن را null کن
            if (empty($user->phone) || $user->phone === '') {
                $user->phone = null;
            }

            // validation: باید حداقل یکی از email یا phone وجود داشته باشد
            if (is_null($user->email) && is_null($user->phone)) {
                throw new \Exception('User must have either email or phone number');
            }
        });
    }

    /**
     * Get available registration types
     */
    public static function registrationTypes(): array
    {
        return [
            self::REGISTRATION_NORMAL => 'Normal Registration',
            self::REGISTRATION_GOOGLE => 'Google OAuth',
        ];
    }

    /**
     * Check if user registered via Google
     */
    public function isGoogleRegistration(): bool
    {
        return $this->registration_type === self::REGISTRATION_GOOGLE;
    }

    /**
     * Check if user registered normally
     */
    public function isNormalRegistration(): bool
    {
        return $this->registration_type === self::REGISTRATION_NORMAL;
    }

    /**
     * Genders list
     */
    public static function genders(): array
    {
        return [
            self::MALE => 'male',
            self::FEMALE => 'female',
            self::CUSTOM => 'custom',
        ];
    }

    /**
     * Set password attribute with hashing
     */
    public function setPasswordAttribute($password): void
    {
        if ($password) {
            $this->attributes['password'] = Hash::needsRehash($password) ? Hash::make($password) : $password;
        }
    }

    /**
     * Get the default profile photo URL if no profile photo has been uploaded.
     */
    public function defaultProfilePhotoUrl(): string
    {
        // Use Google avatar if available
        if ($this->avatar) {
            return $this->avatar;
        }

        $name = trim(collect(explode(' ', $this->name))->map(function ($segment) {
            return mb_substr($segment, 0, 1);
        })->join(' '));

        return asset('image/user.svg');
    }

    // Rest of the methods remain the same...
    public function hasGoogleAccount(): bool
    {
        return !is_null($this->google_id);
    }

    public function isGoogleTokenExpired(): bool
    {
        if (!$this->google_token_expires_at) {
            return true;
        }
        return $this->google_token_expires_at->isPast();
    }

    public function canLogin(): bool
    {
        return !is_null($this->password) || !is_null($this->google_id);
    }

    public function hasVerifiedContact(): bool
    {
        return !is_null($this->email_verified_at) || !is_null($this->phone_verified_at);
    }

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function hasVerifiedPhone(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    public function markPhoneAsVerified(): bool
    {
        return $this->forceFill([
            'phone_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    public function getPrimaryContactAttribute(): string
    {
        if ($this->email) {
            return $this->email;
        }

        if ($this->phone) {
            return $this->country_code . $this->phone;
        }

        return 'No contact method';
    }

    public function isAdmin(): bool
    {
        return $this->is_admin ?? false;
    }

    public function isActive(): bool
    {
        return $this->is_active ?? true;
    }

    // Scopes
    public function scopeByPhone($query, $phone)
    {
        return $query->where('phone', $phone);
    }

    public function scopeByEmail($query, $email)
    {
        return $query->where('email', $email);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    public function scopeVerified($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('email_verified_at')
                ->orWhereNotNull('phone_verified_at');
        });
    }

    public function scopeGoogleUsers($query)
    {
        return $query->where('registration_type', self::REGISTRATION_GOOGLE);
    }

    public function scopeNormalUsers($query)
    {
        return $query->where('registration_type', self::REGISTRATION_NORMAL);
    }
}
