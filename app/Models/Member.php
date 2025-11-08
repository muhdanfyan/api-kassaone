<?php

namespace App\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Member extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasCuid;

    /**
     * Member Type Constants
     */
    const MEMBER_TYPE_PENDIRI = 'Pendiri';
    const MEMBER_TYPE_BIASA = 'Biasa';
    const MEMBER_TYPE_CALON = 'Calon';
    const MEMBER_TYPE_KEHORMATAN = 'Kehormatan';

    /**
     * Verification Status Constants
     */
    const VERIFICATION_PENDING = 'pending';
    const VERIFICATION_PAYMENT_PENDING = 'payment_pending';
    const VERIFICATION_VERIFIED = 'verified';
    const VERIFICATION_REJECTED = 'rejected';

    /**
     * Get all member types
     */
    public static function getMemberTypes(): array
    {
        return [
            self::MEMBER_TYPE_PENDIRI,
            self::MEMBER_TYPE_BIASA,
            self::MEMBER_TYPE_CALON,
            self::MEMBER_TYPE_KEHORMATAN,
        ];
    }

    /**
     * Get all verification statuses
     */
    public static function getVerificationStatuses(): array
    {
        return [
            self::VERIFICATION_PENDING,
            self::VERIFICATION_PAYMENT_PENDING,
            self::VERIFICATION_VERIFIED,
            self::VERIFICATION_REJECTED,
        ];
    }

    /**
     * Validation rule for member_type
     */
    public static function memberTypeRule(): string
    {
        return 'in:' . implode(',', self::getMemberTypes());
    }

    /**
     * Validation rule for verification_status
     */
    public static function verificationStatusRule(): string
    {
        return 'in:' . implode(',', self::getVerificationStatuses());
    }

    protected $fillable = [
        'member_id_number',
        'full_name',
        'username',
        'password',
        'email',
        'phone_number',
        'address',
        'ktp_scan',
        'selfie_with_ktp',
        'nik',
        'join_date',
        'member_type',
        'status',
        'role_id',
        'position',
        'verification_status',
        'payment_amount',
        'payment_upload_token',
        'payment_proof',
        'payment_uploaded_at',
        'payment_verified_at',
        'payment_verified_by',
        'rejected_reason',
        'rejected_at',
        'rejected_by',
        // Informasi Pribadi
        'gender',
        'birth_place',
        'birth_date',
        'religion',
        'education',
        'occupation',
        'office_name',
        'marital_status',
        // Informasi Ahli Waris
        'heir_name',
        'heir_relationship',
        'heir_address',
        'heir_phone',
        // Simpanan
        'monthly_savings_amount',
    ];

    protected $hidden = [
        'password',
    ];

    protected $appends = [
        'name',
        'date_joined',
    ];

    protected $casts = [
        'join_date' => 'date',
        'birth_date' => 'date',
        'payment_amount' => 'decimal:2',
        'monthly_savings_amount' => 'decimal:2',
    ];

    /**
     * Accessor for 'name' attribute (backward compatibility)
     */
    public function getNameAttribute()
    {
        return $this->full_name;
    }

    /**
     * Accessor for 'date_joined' attribute (backward compatibility)
     */
    public function getDateJoinedAttribute()
    {
        return $this->join_date;
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Savings accounts relationship
     */
    public function savingsAccounts()
    {
        return $this->hasMany(SavingsAccount::class, 'member_id', 'id');
    }

    /**
     * Transactions relationship
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'member_id', 'id');
    }

    /**
     * Check if member is Pendiri
     */
    public function isPendiri(): bool
    {
        return $this->member_type === self::MEMBER_TYPE_PENDIRI;
    }

    /**
     * Check if member is Biasa
     */
    public function isBiasa(): bool
    {
        return $this->member_type === self::MEMBER_TYPE_BIASA;
    }

    /**
     * Check if member is Calon
     */
    public function isCalon(): bool
    {
        return $this->member_type === self::MEMBER_TYPE_CALON;
    }

    /**
     * Check if verification is pending
     */
    public function isPending(): bool
    {
        return $this->verification_status === self::VERIFICATION_PENDING;
    }

    /**
     * Check if payment is pending
     */
    public function isPaymentPending(): bool
    {
        return $this->verification_status === self::VERIFICATION_PAYMENT_PENDING;
    }

    /**
     * Check if member is verified
     */
    public function isVerified(): bool
    {
        return $this->verification_status === self::VERIFICATION_VERIFIED;
    }

    /**
     * Check if member is rejected
     */
    public function isRejected(): bool
    {
        return $this->verification_status === self::VERIFICATION_REJECTED;
    }

    /**
     * Check if member can login
     * Allow pending users to login but with restricted access
     */
    public function canLogin(): bool
    {
        // Allow verified and pending users to login
        return in_array($this->verification_status, [
            self::VERIFICATION_VERIFIED,
            self::VERIFICATION_PENDING,
            self::VERIFICATION_PAYMENT_PENDING
        ]) && $this->status === 'Aktif';
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [
            'member_id_number' => $this->member_id_number,
            'username' => $this->username,
            'email' => $this->email,
            'role_id' => $this->role_id,
            'role_name' => $this->role?->name,
        ];
    }
}
