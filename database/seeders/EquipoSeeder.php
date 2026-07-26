<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// Cuentas reales de los integrantes del equipo (Proyecto Integrador de Saberes),
// no datos de demostración. Contraseña inicial igual para todos: deben cambiarla
// desde Configuración > Mi cuenta (PUT /me) la primera vez que entren.
class EquipoSeeder extends Seeder
{
    public function run(): void
    {
        $integrantes = [
            ['name' => 'Cheffir Levintong', 'apellido' => 'Alvarado Chancay', 'cedula' => '0900000019', 'email' => 'calvarado@est.istg.edu.ec'],
            ['name' => 'Jenniffer Yajaira', 'apellido' => 'Corozo Chávez', 'cedula' => '0900000027', 'email' => 'jcorozo@est.istg.edu.ec'],
            ['name' => 'Blanca Estela', 'apellido' => 'Marcillo Arteaga', 'cedula' => '0900000035', 'email' => 'bmarcillo@est.istg.edu.ec'],
            ['name' => 'Gregorio Enrique', 'apellido' => 'Osorio Andrade', 'cedula' => '0900000043', 'email' => 'geosorio@est.istg.edu.ec'],
            ['name' => 'César Orlando', 'apellido' => 'Tenorio Merchán', 'cedula' => '0900000050', 'email' => 'ctenorio@est.istg.edu.ec'],
        ];

        foreach ($integrantes as $datos) {
            $usuario = User::firstOrCreate(
                ['email' => $datos['email']],
                [
                    'name' => $datos['name'],
                    'apellido' => $datos['apellido'],
                    'cedula' => $datos['cedula'],
                    'password' => Hash::make('password123'),
                    'activo' => true,
                ]
            );
            $usuario->syncRoles(['Administrador']);
        }
    }
}
