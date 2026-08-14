<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',
            'roles.gestionar',
            'permisos.gestionar',
            'brigadas.gestionar',
            'pacientes.gestionar',
            'turnos.gestionar',
            'reportes.ver',
            'medicos.gestionar',
            'brigadistas.gestionar',
            'comunidades.gestionar',
            'noticias.gestionar',
            // Exclusivo de Administrador (configuración general de la plataforma, distinto
            // de "administrar campañas" que es responsabilidad de Coordinador). Reservado
            // para un futuro endpoint de configuración global; hoy ningún endpoint lo exige.
            'configuracion.gestionar',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Administrador: todos los permisos, incluido configuracion.gestionar.
        $administrador = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $administrador->syncPermissions($permissions);

        // Coordinador: todos los permisos operativos de campañas, sin configuracion.gestionar.
        $coordinador = Role::firstOrCreate(['name' => 'Coordinador', 'guard_name' => 'web']);
        $coordinador->syncPermissions(array_diff($permissions, ['configuracion.gestionar']));

        $brigadista = Role::firstOrCreate(['name' => 'Brigadista', 'guard_name' => 'web']);
        $brigadista->syncPermissions([
            'brigadas.gestionar',
            'pacientes.gestionar',
            'turnos.gestionar',
        ]);

        // Médico: consulta su operación asignada y completa el flujo clínico de turnos.
        $medico = Role::firstOrCreate(['name' => 'Medico', 'guard_name' => 'web']);
        $medico->syncPermissions([
            'pacientes.gestionar',
            'turnos.gestionar',
        ]);

        // Ciudadano: usa únicamente endpoints públicos y de autoservicio (/me y preferencias).
        // No recibe permisos administrativos.
        $ciudadano = Role::firstOrCreate(['name' => 'Ciudadano', 'guard_name' => 'web']);
        $ciudadano->syncPermissions([]);

        $admin = User::firstOrCreate(
            ['email' => 'coordinador@brigadasalud.test'],
            [
                'name' => 'Coordinador General',
                'cedula' => '1717171712',
                'password' => Hash::make('password123'),
                'activo' => true,
            ]
        );
        $admin->assignRole('Coordinador');

        $administradorUsuario = User::firstOrCreate(
            ['email' => 'administrador@brigadasalud.test'],
            [
                'name' => 'Administrador de la Plataforma',
                'cedula' => '0909090904',
                'password' => Hash::make('password123'),
                'activo' => true,
            ]
        );
        $administradorUsuario->assignRole('Administrador');
    }
}
