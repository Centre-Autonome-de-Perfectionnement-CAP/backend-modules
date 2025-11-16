<?php

namespace App\Services;

use Illuminate\Support\Str;

class PasswordGeneratorService
{
    /**
     * Génère un mot de passe sécurisé aléatoire
     */
    public function generate(int $length = 12, bool $includeSpecialChars = true): string
    {
        $length = max(8, $length);
        
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $characters = $uppercase . $lowercase . $numbers;
        if ($includeSpecialChars) {
            $characters .= $specialChars;
        }
        
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        
        if ($includeSpecialChars) {
            $password .= $specialChars[random_int(0, strlen($specialChars) - 1)];
        }
        
        $remainingLength = $length - strlen($password);
        for ($i = 0; $i < $remainingLength; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return str_shuffle($password);
    }
}
