<?php

namespace App\Console\Commands;

use \App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class UserManagement extends Command
{
    protected $signature = "user:management";
    protected $description = "Utility to manage users with actions like edit,create,password reset";

    public function handle(): void
    {
        $actionSelector = (int) $this->ask(
            "Witch action do you want to do?
               => 1. Password reset
               => 2. User creation
               => 3. User edit"
        );

        match ($actionSelector) {
            1 => $this->resetPassword(),
            // 2 => $this->updateUser(),
            3 => $this->createUser(),
        };
    }

    public function resetPassword(): bool
    {
        $userModel = $this->getUser();

        passwordType:
        $password = $this->secret("Please specify your new password: "); # Password.123
        $confirmPassword = $this->secret("Re-type your password"); # Password.123
        // TODO: Add validation

        if ($password !== $confirmPassword) {
            $this->error('Your password doesn\'t match');

            if ($this->confirm("Do you wish to repeat?")) {
                goto passwordType;
            }

            return false;
        }

        $userModel->update([
            "password" => Hash::make($password),
        ]);

        $this->info("Your password was successfuly changed!");
    }

    public function updateUser(User $userModel)
    {
        $userModel = $this->getUser();
    }

    public function createUser()
    {
        $data = [];
        $userFillable = (new User())->getFillable();
        foreach ($userFillable as $field) {
            if ($field === "password") {
                $data[$field] = Hash::make(
                    $this->secret("Insert your password:")
                );
            }

            $data[$field] = $this->ask("Insert your " . $field . ": ");
        }

        $user = User::query()->create($data);

        if ($user) {
            $this->info("Your user was created succesfuly!");
            return true;
        }

        $this->error("Your user was not created succesfuly");
        return false;
    }

    public function getUser()
    {
        $userFillable = (new User())->getFillable();
        unset($userFillable[array_search("password", $userFillable)]);

        $stringFillable = "";
        foreach ($userFillable as $key => $fillable) {
            $stringFillable .= "\n {$key} => {$fillable}";
        }

        (string) ($querySelector = $this->ask(
            "How do you want to select user? \n" . $stringFillable
        ));

        (string) ($searchTerm = $this->ask(
            "Insert " . $userFillable[$querySelector] . ": "
        ));

        $user = User::query()
            ->where([
                $userFillable[$querySelector] => $searchTerm,
            ])
            ->first();

        if (!$user) {
            $this->error('Your user wasn\'t found');
            die();
        }

        return $user;
    }
}

