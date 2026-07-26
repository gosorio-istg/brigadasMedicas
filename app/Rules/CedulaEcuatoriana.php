<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

// Algoritmo oficial de validación de cédula ecuatoriana (módulo 10):
// código de provincia (01-24) + tercer dígito de persona natural (0-5) + dígito verificador.
class CedulaEcuatoriana implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^\d{10}$/', $value)) {
            $fail('El campo :attribute debe tener 10 dígitos numéricos.');

            return;
        }

        $digitos = array_map('intval', str_split($value));

        $provincia = $digitos[0] * 10 + $digitos[1];
        if ($provincia < 1 || $provincia > 24) {
            $fail('El campo :attribute no corresponde a un código de provincia válido.');

            return;
        }

        if ($digitos[2] > 5) {
            $fail('El campo :attribute no corresponde a una cédula de persona natural válida.');

            return;
        }

        $suma = 0;
        foreach (array_slice($digitos, 0, 9) as $posicion => $digito) {
            if ($posicion % 2 === 0) {
                $digito *= 2;
                if ($digito > 9) {
                    $digito -= 9;
                }
            }
            $suma += $digito;
        }

        $verificador = (10 - ($suma % 10)) % 10;

        if ($verificador !== $digitos[9]) {
            $fail('El campo :attribute no es una cédula ecuatoriana válida.');
        }
    }
}
