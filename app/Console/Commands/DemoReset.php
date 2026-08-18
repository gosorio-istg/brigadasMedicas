<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

// Borra TODA la base de datos y la vuelve a poblar con datos de demostración
// (coordinadores, brigadistas, médicos con cuenta propia, ciudadanos, campañas en cada
// estado, pacientes y turnos). Pensado solo para entornos de desarrollo/pruebas: se niega
// a correr en producción salvo que se le pase --force explícitamente, igual que hace
// el propio `migrate:fresh` de Laravel.
class DemoReset extends Command
{
    protected $signature = 'demo:reset {--force : Permite correrlo aunque el entorno no sea local/testing}';

    protected $description = 'Borra todos los datos y siembra una demo completa (roles, usuarios, campañas de prueba)';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing']) && ! $this->option('force')) {
            $this->error('demo:reset solo corre en entornos local/testing. Usa --force si de verdad quieres correrlo aquí (esto borra TODOS los datos).');

            return self::FAILURE;
        }

        $this->warn('Borrando toda la base de datos y sembrando datos de demostración...');

        Artisan::call('migrate:fresh', ['--force' => true], $this->output);
        Artisan::call('db:seed', ['--force' => true], $this->output);

        $this->info('Listo. Cuentas de demostración (contraseña para todas: password123):');
        $this->table(['Rol', 'Correo'], [
            ['Administrador', 'geosorio@est.istg.edu.ec (y el resto del equipo, ver EquipoSeeder)'],
            ['Coordinador', 'coordinador@brigadasalud.test'],
            ['Coordinador', 'coordinador2@brigadasalud.test'],
            ['Médico', 'medico1@brigadasalud.test … medico9@brigadasalud.test'],
            ['Brigadista', 'brigadista1@brigadasalud.test … brigadista3@brigadasalud.test'],
            ['Ciudadano', 'ciudadano1@brigadasalud.test, ciudadano2@brigadasalud.test'],
        ]);

        return self::SUCCESS;
    }
}
