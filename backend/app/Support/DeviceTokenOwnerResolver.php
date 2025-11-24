<?php

namespace App\Support;

use App\Models\Doctor;
use App\Models\User;
use App\Models\VetRegisterationTemp;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Maps incoming owner_model hints to allowed Eloquent models.
 */
class DeviceTokenOwnerResolver
{
    private static array $map = [
        'user'                        => User::class,
        User::class                   => User::class,
        'doctor'                      => Doctor::class,
        Doctor::class                 => Doctor::class,
        'vet'                         => VetRegisterationTemp::class,
        'clinic'                      => VetRegisterationTemp::class,
        'clinic_admin'                => VetRegisterationTemp::class,
        'vet_registeration_temp'      => VetRegisterationTemp::class,
        'vetregistrationtemp'         => VetRegisterationTemp::class,
        VetRegisterationTemp::class   => VetRegisterationTemp::class,
    ];

    public static function resolve(?string $ownerModel): string
    {
        if (!$ownerModel) {
            return User::class;
        }

        $normalized = strtolower(trim($ownerModel, " \\"));

        if (isset(self::$map[$normalized])) {
            return self::$map[$normalized];
        }

        // If caller passed an FQCN, allow only Eloquent models
        if (class_exists($ownerModel) && is_subclass_of($ownerModel, Model::class)) {
            return $ownerModel;
        }

        throw new InvalidArgumentException('Unsupported owner_model provided.');
    }

    public static function detectOwnerModelForId(int $ownerId): ?string
    {
        foreach (self::allowedModelClasses() as $candidate) {
            if ($candidate::query()->whereKey($ownerId)->exists()) {
                return $candidate;
            }
        }

        return null;
    }

    private static function allowedModelClasses(): array
    {
        return array_values(array_unique(self::$map));
    }
}
