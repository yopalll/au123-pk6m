<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $parts     = preg_split('/\s+/', trim($input['name']), 2);
        $firstName = $parts[0] ?: $input['name'];
        $lastName  = $parts[1] ?? null;

        return User::create([
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $input['email'],
            'password'   => $input['password'],
            'role'       => 'customer',
        ]);
    }
}
