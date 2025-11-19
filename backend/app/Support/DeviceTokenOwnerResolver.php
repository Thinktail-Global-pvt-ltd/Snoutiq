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
    public static function resolve(?string $ownerModel): string
    {
        if (!$ownerModel) {
            return User::class;
        }

        $normalized = strtolower(trim($ownerModel, " \\"));

        $map = [
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

        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        // If caller passed an FQCN, allow only Eloquent models
        if (class_exists($ownerModel) && is_subclass_of($ownerModel, Model::class)) {
            return $ownerModel;
        }

        throw new InvalidArgumentException('Unsupported owner_model provided.');
    }
}
