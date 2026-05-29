<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'role', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_SUPPORT = 'support';

    public const ROLE_NOC = 'noc';

    public const ROLE_TECHNICIAN = 'technician';

    public const ROLE_CUSTOMER = 'customer';

    /**
     * @return array<string, string>
     */
    public static function roleOptions(): array
    {
        return [
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_MANAGER => 'Manager',
            self::ROLE_SUPPORT => 'Support',
            self::ROLE_NOC => 'NOC',
            self::ROLE_TECHNICIAN => 'Technician',
            self::ROLE_CUSTOMER => 'Customer',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function panelAccessRoles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_MANAGER,
            self::ROLE_SUPPORT,
            self::ROLE_NOC,
            self::ROLE_TECHNICIAN,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function internalRoles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_MANAGER,
            self::ROLE_SUPPORT,
            self::ROLE_NOC,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function ticketAssignmentRoles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_MANAGER,
            self::ROLE_SUPPORT,
            self::ROLE_NOC,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function dashboardRoles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_MANAGER,
            self::ROLE_SUPPORT,
            self::ROLE_NOC,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function ticketCreateRoles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_SUPPORT,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function ticketUpdateRoles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_SUPPORT,
            self::ROLE_NOC,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function ticketCloseRoles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_MANAGER,
            self::ROLE_SUPPORT,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function jobPhotoUploadRoles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_SUPPORT,
        ];
    }

    public function technician(): HasOne
    {
        return $this->hasOne(Technician::class);
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(self::panelAccessRoles());
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function isManager(): bool
    {
        return $this->hasRole(self::ROLE_MANAGER);
    }

    public function isSupport(): bool
    {
        return $this->hasRole(self::ROLE_SUPPORT);
    }

    public function isNoc(): bool
    {
        return $this->hasRole(self::ROLE_NOC);
    }

    public function isTechnician(): bool
    {
        return $this->hasRole(self::ROLE_TECHNICIAN);
    }

    public function isCustomer(): bool
    {
        return $this->hasRole(self::ROLE_CUSTOMER);
    }

    public function canViewTickets(): bool
    {
        return $this->hasAnyRole(self::internalRoles());
    }

    public function canCreateTickets(): bool
    {
        return $this->hasAnyRole(self::ticketCreateRoles());
    }

    public function canUpdateTickets(): bool
    {
        return $this->hasAnyRole(self::ticketUpdateRoles());
    }

    public function canCloseTickets(): bool
    {
        return $this->hasAnyRole(self::ticketCloseRoles());
    }

    public function canAddTicketComments(): bool
    {
        return $this->hasAnyRole(self::internalRoles());
    }

    public function canAssignTickets(): bool
    {
        return $this->hasAnyRole(self::ticketAssignmentRoles());
    }

    public function canViewAllTechnicianJobs(): bool
    {
        return $this->hasAnyRole(self::internalRoles());
    }

    public function canManageTechnicianJobs(): bool
    {
        return $this->isAdmin();
    }

    public function canUploadJobPhotos(): bool
    {
        return $this->hasAnyRole(self::jobPhotoUploadRoles());
    }

    public function canCancelTechnicianJobs(): bool
    {
        return $this->hasAnyRole(self::internalRoles());
    }

    public function canViewCustomers(): bool
    {
        return $this->hasAnyRole(self::internalRoles());
    }

    public function canManageCustomers(): bool
    {
        return $this->isAdmin();
    }

    public function canViewTechnicians(): bool
    {
        return $this->hasAnyRole(self::internalRoles());
    }

    public function canManageTechnicians(): bool
    {
        return $this->isAdmin();
    }

    public function canViewCompanyDashboard(): bool
    {
        return $this->hasAnyRole(self::dashboardRoles());
    }

    public function canViewTechnicianDashboard(): bool
    {
        return $this->isTechnician() && $this->technicianProfileId() !== null;
    }

    public function technicianProfileId(): ?int
    {
        if (! $this->isTechnician()) {
            return null;
        }

        $technicianId = $this->technician?->getKey();

        return $technicianId === null ? null : (int) $technicianId;
    }

    public function customerProfileId(): ?int
    {
        if (! $this->isCustomer()) {
            return null;
        }

        $customerId = $this->customer?->getKey();

        return $customerId === null ? null : (int) $customerId;
    }

    public function issueApiToken(string $name = 'portal'): string
    {
        $plainTextToken = Str::random(80);

        $this->apiTokens()->create([
            'name' => $name,
            'token_hash' => hash('sha256', $plainTextToken),
        ]);

        return $plainTextToken;
    }

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
            'role' => 'string',
        ];
    }
}
