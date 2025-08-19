<?php

namespace App\Service\User;

class TokenGenerator
{
    private const ALLOWED_CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';
    private const MIN_LENGTH = 20;
    private const MAX_LENGTH = 30;

    /**
     * Genera un token aleatorio adecuado para URL con una longitud entre 20 y 30 caracteres.
     *
     * @return string El token generado
     */
    public function generate(): string
    {
        $length = random_int(self::MIN_LENGTH, self::MAX_LENGTH);
        $token = '';

        for ($i = 0; $i < $length; $i++) {
            $token .= self::ALLOWED_CHARS[random_int(0, strlen(self::ALLOWED_CHARS) - 1)];
        }

        return $token;
    }
}