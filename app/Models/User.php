<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Constants\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected $table = 'users';

    protected $primaryKey = 'id_user';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone_number',
        'profile_url',
    ];

    // Prevent mass-assignment privilege escalation (BUG-A02 / SEC-03).
    // Use explicit property assignment: $user->role = '...'; $user->save();
    protected $guarded = ['role', 'is_active', 'id_user'];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ──── Accessors ─────────────────────────────────────────

    /**
     * Nama lengkap user (first_name + last_name).
     *
     * Defensive against legacy/test records where someone copy-pasted the same
     * value into both fields (e.g. "yosua" / "yosua" → rendered as "yosua yosua")
     * — case-insensitive match collapses the duplicate down to a single name.
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $first = trim((string) $this->first_name);
                $last = trim((string) ($this->last_name ?? ''));

                if ($last === '' || strcasecmp($first, $last) === 0) {
                    return $first;
                }

                return trim($first.' '.$last);
            },
        );
    }

    /**
     * Filament v5 calls getUserName() which reads $user->name.
     * Alias to full_name for compatibility.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->full_name,
        );
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->full_name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    // ──── Relasi ────────────────────────────────────────────

    public function salons(): HasMany
    {
        return $this->hasMany(Salon::class, 'id_user');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'id_user');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'id_user');
    }

    public function pembayarans(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'id_user');
    }

    /**
     * Many-to-Many: Promo yang dimiliki/diklaim user.
     */
    public function promos()
    {
        return $this->belongsToMany(Promo::class, 'user_promo', 'id_user', 'id_promo')
            ->withPivot('is_used', 'used_at')
            ->withTimestamps();
    }

    /**
     * Many-to-Many: Salon yang difavoritkan user (wishlist).
     */
    public function favourites()
    {
        return $this->belongsToMany(Salon::class, 'user_favourites', 'id_user', 'id_salon')
            ->withTimestamps();
    }

    /**
     * Convenience helper used by the salon-card heart icon.
     */
    public function hasFavourited(int $idSalon): bool
    {
        return $this->favourites()->whereKey($idSalon)->exists();
    }

    // ──── Relasi V2 (E-commerce, Empty Return, Community) ────

    public function cartItems(): HasMany
    {
        return $this->hasMany(Cart::class, 'id_user', 'id_user');
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class, 'id_user', 'id_user');
    }

    public function productOrders(): HasMany
    {
        return $this->hasMany(ProductOrder::class, 'id_user', 'id_user');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class, 'id_user', 'id_user');
    }

    public function skincareProfile(): HasOne
    {
        return $this->hasOne(UserSkincareProfile::class, 'id_user', 'id_user');
    }

    public function points(): HasOne
    {
        return $this->hasOne(UserPoint::class, 'id_user', 'id_user');
    }

    public function emptyReturns(): HasMany
    {
        return $this->hasMany(EmptyReturn::class, 'id_user', 'id_user');
    }

    public function forumThreads(): HasMany
    {
        return $this->hasMany(ForumThread::class, 'id_user', 'id_user');
    }

    public function communityPoint(): HasOne
    {
        return $this->hasOne(CommunityPoint::class, 'id_user', 'id_user');
    }

    public function badges(): HasMany
    {
        return $this->hasMany(UserBadge::class, 'id_user', 'id_user');
    }

    // ──── Filament ──────────────────────────────────────────

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->role === UserRole::ADMIN && $this->is_active;
        }

        if ($panel->getId() === 'owner') {
            return $this->role === UserRole::SALON_OWNER && $this->is_active;
        }

        if ($panel->getId() === 'store') {
            return in_array($this->role, [UserRole::ADMIN_STORE, UserRole::ADMIN], true)
                && $this->is_active;
        }

        return false;
    }

    /**
     * Convenience: collection of salon ids belonging to this owner.
     * Used by Owner panel resources to scope queries.
     */
    public function ownedSalonIds(): array
    {
        return $this->salons()->pluck('id_salon')->all();
    }
}
