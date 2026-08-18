<?php

namespace Database\Seeders;

use App\Models\Brigada;
use App\Models\Comunidad;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Noticia;
use App\Models\Paciente;
use App\Models\SolicitudBrigada;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

// Datos de ejemplo (no aleatorios) para poder probar cada pantalla del frontend con casos variados:
// brigadas en distintos estados, médicos con distinta disponibilidad, pacientes de distintas edades
// y turnos en cada estado posible. Usa firstOrCreate/updateOrCreate para poder correr varias veces
// (php artisan db:seed) sin duplicar filas.
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $especialidades = $this->sembrarEspecialidades();
        $usuarios = $this->sembrarUsuarios();
        $this->sembrarCiudadanos();
        $medicos = $this->sembrarMedicos($especialidades);
        $brigadas = $this->sembrarBrigadas($especialidades, $usuarios, $medicos);
        $this->sembrarBrigadistas($brigadas, $usuarios);
        $pacientes = $this->sembrarPacientes();
        $this->sembrarTurnos($brigadas, $especialidades, $pacientes, $medicos, $usuarios);
        $this->sembrarComunidades();
        $this->sembrarNoticias($usuarios);
        $this->sembrarSolicitudesBrigada($brigadas);
    }

    // Busca por email O cédula antes de crear: firstOrCreate() solo compara por el atributo
    // que se le pasa (aquí, email), así que si ya existe OTRO usuario con la misma cédula
    // (de una siembra parcial anterior, o creado a mano desde la web) el INSERT choca contra
    // la restricción UNIQUE de "cedula" y aborta todo el seeder a mitad de camino.
    private function resolveUsuario(string $email, string $cedula, array $atributos): User
    {
        $usuario = User::where('email', $email)->orWhere('cedula', $cedula)->first();

        return $usuario ?: User::create(array_merge(['email' => $email, 'cedula' => $cedula], $atributos));
    }

    // Cuentas de Ciudadano: para probar registro/login desde la app móvil, solicitar
    // campañas y confirmar asistencia (ninguna de las otras siembras crea este rol).
    private function sembrarCiudadanos(): void
    {
        $datos = [
            ['email' => 'ciudadano1@brigadas.com', 'name' => 'Rosa', 'apellido' => 'Vera Cedeño', 'cedula' => '0911111116', 'sector' => 'Cooperativa Suárez'],
            ['email' => 'ciudadano2@brigadas.com', 'name' => 'Manuel', 'apellido' => 'Reyes Baque', 'cedula' => '0922222227', 'sector' => 'Isla Trinitaria'],
        ];

        foreach ($datos as $dato) {
            $usuario = $this->resolveUsuario($dato['email'], $dato['cedula'], [
                'name' => $dato['name'],
                'apellido' => $dato['apellido'],
                'sector' => $dato['sector'],
                'password' => Hash::make('123'),
                'activo' => true,
            ]);
            $usuario->syncRoles(['Ciudadano']);
        }
    }

    private function sembrarEspecialidades(): array
    {
        $nombres = ['Medicina General', 'Pediatría', 'Odontología', 'Ginecología', 'Psicología', 'Enfermería', 'Oftalmología'];

        $especialidades = [];
        foreach ($nombres as $nombre) {
            $especialidades[$nombre] = Especialidad::firstOrCreate(['nombre' => $nombre], ['activa' => true]);
        }

        return $especialidades;
    }

    private function sembrarUsuarios(): array
    {
        $datos = [
            ['email' => 'coordinador2@brigadas.com', 'name' => 'Roberto Iván Salazar', 'cedula' => '0101010106', 'rol' => 'Coordinador'],
            ['email' => 'brigadista1@brigadas.com', 'name' => 'María Fernanda Ochoa', 'cedula' => '2424242424', 'rol' => 'Brigadista'],
            ['email' => 'brigadista2@brigadas.com', 'name' => 'Carlos Andrés Zambrano', 'cedula' => '1313131318', 'rol' => 'Brigadista'],
            ['email' => 'brigadista3@brigadas.com', 'name' => 'Lucía Paola Mendoza', 'cedula' => '0808080808', 'rol' => 'Brigadista'],
        ];

        $usuarios = [];
        foreach ($datos as $dato) {
            $usuario = $this->resolveUsuario($dato['email'], $dato['cedula'], [
                'name' => $dato['name'], 'password' => Hash::make('123'), 'activo' => true,
            ]);
            $usuario->assignRole($dato['rol']);
            $usuarios[$dato['email']] = $usuario;
        }

        return $usuarios;
    }

    private function sembrarMedicos(array $especialidades): array
    {
        $datos = [
            ['credencial_cmp' => 'CMP-1001', 'nombres' => 'Dra. Jenniffer Yajaira', 'apellido' => 'Corozo Chávez', 'cedula' => '0931000011', 'email' => 'medico1@brigadas.com', 'especialidad' => 'Medicina General', 'telefono' => '0991000001', 'disponible' => true],
            ['credencial_cmp' => 'CMP-1002', 'nombres' => 'Dr. Jenniffer2 Yajaira2', 'apellido' => 'Corozo2 Chávez2', 'cedula' => '0931000029', 'email' => 'medico2@brigadas.com', 'especialidad' => 'Medicina General', 'telefono' => '0991000002', 'disponible' => true],
            ['credencial_cmp' => 'CMP-1003', 'nombres' => 'Dra. Jenniffer3 Yajaira3', 'apellido' => 'Corozo3 Chávez3', 'cedula' => '0931000037', 'email' => 'medico3@brigadas.com', 'especialidad' => 'Pediatría', 'telefono' => '0991000003', 'disponible' => true],
            ['credencial_cmp' => 'CMP-1004', 'nombres' => 'Dr. Esteban Ramírez', 'apellido' => 'Ramírez Vélez', 'cedula' => '0931000045', 'email' => 'medico4@brigadas.com', 'especialidad' => 'Pediatría', 'telefono' => '0991000004', 'disponible' => false],
            ['credencial_cmp' => 'CMP-1005', 'nombres' => 'Dra. Jenniffer4 Yajaira4', 'apellido' => 'Corozo4 Chávez4', 'cedula' => '0931000052', 'email' => 'medico5@brigadas.com', 'especialidad' => 'Odontología', 'telefono' => '0991000005', 'disponible' => true],
            ['credencial_cmp' => 'CMP-1006', 'nombres' => 'Dra. Verónica Chávez', 'apellido' => 'Chávez Loor', 'cedula' => '0931000060', 'email' => 'medico6@brigadas.com', 'especialidad' => 'Ginecología', 'telefono' => '0991000006', 'disponible' => true],
            ['credencial_cmp' => 'CMP-1007', 'nombres' => 'Lcda. Gabriela Muñoz', 'apellido' => 'Muñoz Zambrano', 'cedula' => '0931000078', 'email' => 'medico7@brigadas.com', 'especialidad' => 'Psicología', 'telefono' => '0991000007', 'disponible' => true],
            ['credencial_cmp' => 'CMP-1008', 'nombres' => 'Lcdo. Kevin Alexander Loor', 'apellido' => 'Loor Cedeño', 'cedula' => '0931000086', 'email' => 'medico8@brigadas.com', 'especialidad' => 'Enfermería', 'telefono' => '0991000008', 'disponible' => true],
            ['credencial_cmp' => 'CMP-1009', 'nombres' => 'Dr. Patricio Andrade Vélez', 'apellido' => 'Andrade Vélez', 'cedula' => '0931000094', 'email' => 'medico9@brigadas.com', 'especialidad' => 'Oftalmología', 'telefono' => '0991000009', 'disponible' => true],
        ];

        $medicos = [];
        foreach ($datos as $dato) {
            // Cuenta de acceso propia (rol Medico) para poder entrar desde la app móvil,
            // igual que hace MedicoController::store al crear un médico desde la web.
            $usuario = $this->resolveUsuario($dato['email'], $dato['cedula'], [
                'name' => $dato['nombres'],
                'apellido' => $dato['apellido'],
                'password' => Hash::make('123'),
                'activo' => true,
            ]);
            $usuario->syncRoles(['Medico']);

            $medicos[$dato['credencial_cmp']] = Medico::firstOrCreate(
                ['credencial_cmp' => $dato['credencial_cmp']],
                [
                    'user_id' => $usuario->id,
                    'nombres' => $dato['nombres'],
                    'especialidad_id' => $especialidades[$dato['especialidad']]->id,
                    'telefono' => $dato['telefono'],
                    'disponible' => $dato['disponible'],
                ]
            );
        }

        return $medicos;
    }

    private function sembrarBrigadas(array $especialidades, array $usuarios, array $medicos): array
    {
        $coordinadorPrincipal = User::where('email', 'coordinador@brigadasalud.test')->first();
        $coordinadorSecundario = $usuarios['coordinador2@brigadas.com'];

        $datos = [
            [
                'nombre' => 'Brigada Sector Suárez',
                'descripcion' => 'Jornada médica programada para el barrio Cooperativa Suárez.',
                'fecha' => Carbon::now()->addDays(10),
                'ubicacion' => 'Cooperativa Suárez, Guayaquil',
                'estado' => 'programada',
                'coordinador' => $coordinadorPrincipal,
                'especialidades' => ['Medicina General' => 30, 'Pediatría' => 15],
                'medicos' => ['CMP-1001', 'CMP-1003'],
            ],
            [
                'nombre' => 'Brigada Isla Trinitaria',
                'descripcion' => 'Brigada en curso con atención de cuatro especialidades.',
                'fecha' => Carbon::now(),
                'ubicacion' => 'Isla Trinitaria, Guayaquil',
                'estado' => 'en_curso',
                'coordinador' => $coordinadorPrincipal,
                'especialidades' => ['Medicina General' => 25, 'Pediatría' => 10, 'Odontología' => 10, 'Enfermería' => 20],
                'medicos' => ['CMP-1002', 'CMP-1004', 'CMP-1005', 'CMP-1008'],
            ],
            [
                'nombre' => 'Brigada Bastión Popular',
                'descripcion' => 'Jornada finalizada, usada como referencia para reportes.',
                'fecha' => Carbon::now()->subDays(20),
                'ubicacion' => 'Bastión Popular, Guayaquil',
                'estado' => 'finalizada',
                'coordinador' => $coordinadorSecundario,
                'especialidades' => ['Ginecología' => 12, 'Medicina General' => 20],
                'medicos' => ['CMP-1006', 'CMP-1001'],
            ],
            [
                'nombre' => 'Brigada Monte Sinaí',
                'descripcion' => 'Brigada programada con enfoque en salud mental infantil.',
                'fecha' => Carbon::now()->addDays(25),
                'ubicacion' => 'Monte Sinaí, Guayaquil',
                'estado' => 'programada',
                'coordinador' => $coordinadorSecundario,
                'especialidades' => ['Psicología' => 10, 'Pediatría' => 15, 'Odontología' => 10],
                'medicos' => ['CMP-1007', 'CMP-1003', 'CMP-1005'],
            ],
            [
                'nombre' => 'Brigada Nigeria - Los Vergeles',
                'descripcion' => 'Brigada cancelada por lluvias; se reprogramará más adelante.',
                'fecha' => Carbon::now()->subDays(5),
                'ubicacion' => 'Nigeria, Los Vergeles, Guayaquil',
                'estado' => 'cancelada',
                'coordinador' => $coordinadorPrincipal,
                'especialidades' => ['Medicina General' => 20],
                'medicos' => [],
            ],
        ];

        $brigadas = [];
        foreach ($datos as $dato) {
            $brigada = Brigada::firstOrCreate(
                ['nombre' => $dato['nombre']],
                [
                    'descripcion' => $dato['descripcion'],
                    'fecha' => $dato['fecha'],
                    'ubicacion' => $dato['ubicacion'],
                    'estado' => $dato['estado'],
                    'coordinador_id' => $dato['coordinador']->id,
                ]
            );

            $sync = [];
            foreach ($dato['especialidades'] as $nombreEspecialidad => $cupos) {
                $sync[$especialidades[$nombreEspecialidad]->id] = ['cupos' => $cupos];
            }
            $brigada->especialidades()->sync($sync);

            $idsMedicos = array_map(fn ($credencial) => $medicos[$credencial]->id, $dato['medicos']);
            $brigada->medicos()->sync($idsMedicos);

            $brigadas[$dato['nombre']] = $brigada;
        }

        return $brigadas;
    }

    private function sembrarBrigadistas(array $brigadas, array $usuarios): void
    {
        $datos = [
            // Brigada en curso: equipo completo, ambos ya confirmados como asistidos.
            ['brigada' => 'Brigada Isla Trinitaria', 'email' => 'brigadista1@brigadas.com', 'rol_equipo' => 'registro', 'asistio' => true],
            ['brigada' => 'Brigada Isla Trinitaria', 'email' => 'brigadista2@brigadas.com', 'rol_equipo' => 'apoyo_logistico', 'asistio' => true],

            // Brigada finalizada: asistencia ya confirmada, útil para reportes de participación.
            ['brigada' => 'Brigada Bastión Popular', 'email' => 'brigadista1@brigadas.com', 'rol_equipo' => 'registro', 'asistio' => true],
            ['brigada' => 'Brigada Bastión Popular', 'email' => 'brigadista3@brigadas.com', 'rol_equipo' => 'coordinacion', 'asistio' => true],

            // Brigadas programadas: asignados pero con asistencia aún sin confirmar (null).
            ['brigada' => 'Brigada Sector Suárez', 'email' => 'brigadista2@brigadas.com', 'rol_equipo' => 'registro', 'asistio' => null],
            ['brigada' => 'Brigada Monte Sinaí', 'email' => 'brigadista3@brigadas.com', 'rol_equipo' => 'apoyo_logistico', 'asistio' => null],

            // Brigada cancelada: quedó asignado pero no llegó a asistir.
            ['brigada' => 'Brigada Nigeria - Los Vergeles', 'email' => 'brigadista1@brigadas.com', 'rol_equipo' => 'registro', 'asistio' => false],
        ];

        foreach ($datos as $dato) {
            $brigadas[$dato['brigada']]->brigadistas()->syncWithoutDetaching([
                $usuarios[$dato['email']]->id => ['rol_equipo' => $dato['rol_equipo'], 'asistio' => $dato['asistio']],
            ]);
        }
    }

    private function sembrarPacientes(): array
    {
        $datos = [
            ['cedula' => '0912345678', 'nombres' => 'María', 'apellidos' => 'López Cedeño', 'fecha_nacimiento' => '1990-05-10', 'sexo' => 'femenino', 'telefono' => '0981111111', 'sector' => 'Cooperativa Suárez'],
            ['cedula' => '0923456789', 'nombres' => 'Juan', 'apellidos' => 'Pérez Alava', 'fecha_nacimiento' => '1985-02-20', 'sexo' => 'masculino', 'telefono' => '0981111112', 'sector' => 'Isla Trinitaria'],
            ['cedula' => '0934567890', 'nombres' => 'Ana', 'apellidos' => 'Gómez Rivas', 'fecha_nacimiento' => '2001-11-03', 'sexo' => 'femenino', 'telefono' => null, 'sector' => 'Bastión Popular'],
            ['cedula' => '0945678901', 'nombres' => 'Pedro', 'apellidos' => 'Sánchez Bravo', 'fecha_nacimiento' => '1975-07-15', 'sexo' => 'masculino', 'telefono' => '0981111114', 'sector' => 'Monte Sinaí'],
            ['cedula' => '0956789012', 'nombres' => 'Lucía', 'apellidos' => 'Mendoza Vera', 'fecha_nacimiento' => '1968-03-22', 'sexo' => 'femenino', 'telefono' => '0981111115', 'sector' => 'Cooperativa Suárez'],
            ['cedula' => '0967890123', 'nombres' => 'Carlos', 'apellidos' => 'Zambrano Ruiz', 'fecha_nacimiento' => '1999-09-09', 'sexo' => 'masculino', 'telefono' => null, 'sector' => 'Nigeria - Los Vergeles'],
            ['cedula' => '0978901234', 'nombres' => 'Fernanda', 'apellidos' => 'Ochoa Salinas', 'fecha_nacimiento' => '2015-01-30', 'sexo' => 'femenino', 'telefono' => '0981111117', 'sector' => 'Isla Trinitaria'],
            ['cedula' => '0989012345', 'nombres' => 'Roberto', 'apellidos' => 'Salazar Intriago', 'fecha_nacimiento' => '1955-12-01', 'sexo' => 'masculino', 'telefono' => '0981111118', 'sector' => 'Bastión Popular'],
            ['cedula' => '0990123456', 'nombres' => 'Gabriela', 'apellidos' => 'Muñoz Cedeño', 'fecha_nacimiento' => '1993-06-18', 'sexo' => 'otro', 'telefono' => null, 'sector' => 'Monte Sinaí'],
            ['cedula' => '0901234567', 'nombres' => 'Kevin', 'apellidos' => 'Loor Baque', 'fecha_nacimiento' => '2008-08-08', 'sexo' => 'masculino', 'telefono' => '0981111120', 'sector' => 'Cooperativa Suárez'],
        ];

        $pacientes = [];
        foreach ($datos as $dato) {
            $cedula = $dato['cedula'];
            unset($dato['cedula']);
            $pacientes[$cedula] = Paciente::firstOrCreate(['cedula' => $cedula], $dato);
        }

        return $pacientes;
    }

    private function sembrarTurnos(array $brigadas, array $especialidades, array $pacientes, array $medicos, array $usuarios): void
    {
        $registrador = $usuarios['brigadista1@brigadas.com'];

        $datos = [
            // Brigada en curso: un turno en cada estado posible, para poder probar la cola de espera completa.
            ['brigada' => 'Brigada Isla Trinitaria', 'especialidad' => 'Medicina General', 'paciente' => '0923456789', 'medico' => 'CMP-1002', 'estado' => 'atendido', 'horas_desde_registro' => 3],
            ['brigada' => 'Brigada Isla Trinitaria', 'especialidad' => 'Pediatría', 'paciente' => '0978901234', 'medico' => null, 'estado' => 'en_espera', 'horas_desde_registro' => 1],
            ['brigada' => 'Brigada Isla Trinitaria', 'especialidad' => 'Odontología', 'paciente' => '0912345678', 'medico' => null, 'estado' => 'pendiente', 'horas_desde_registro' => 0],
            ['brigada' => 'Brigada Isla Trinitaria', 'especialidad' => 'Enfermería', 'paciente' => '0967890123', 'medico' => null, 'estado' => 'cancelado', 'horas_desde_registro' => 2],

            // Brigada finalizada: turnos ya atendidos, útiles para el módulo de Reportes.
            ['brigada' => 'Brigada Bastión Popular', 'especialidad' => 'Ginecología', 'paciente' => '0934567890', 'medico' => 'CMP-1006', 'estado' => 'atendido', 'horas_desde_registro' => 240],
            ['brigada' => 'Brigada Bastión Popular', 'especialidad' => 'Medicina General', 'paciente' => '0989012345', 'medico' => 'CMP-1001', 'estado' => 'atendido', 'horas_desde_registro' => 239],

            // Brigada cancelada: turno que quedó cancelado junto con la brigada.
            ['brigada' => 'Brigada Nigeria - Los Vergeles', 'especialidad' => 'Medicina General', 'paciente' => '0956789012', 'medico' => null, 'estado' => 'cancelado', 'horas_desde_registro' => 120],
        ];

        // Consecutivo por brigada+especialidad, igual que la regla usada en TurnoController.
        $consecutivos = [];

        foreach ($datos as $dato) {
            $brigada = $brigadas[$dato['brigada']];
            $especialidad = $especialidades[$dato['especialidad']];
            $clave = $brigada->id . '-' . $especialidad->id;
            $consecutivos[$clave] = ($consecutivos[$clave] ?? 0) + 1;

            $horaRegistro = Carbon::now()->subHours($dato['horas_desde_registro']);

            Turno::firstOrCreate(
                [
                    'brigada_id' => $brigada->id,
                    'paciente_id' => $pacientes[$dato['paciente']]->id,
                    'especialidad_id' => $especialidad->id,
                ],
                [
                    'medico_id' => $dato['medico'] ? $medicos[$dato['medico']]->id : null,
                    'numero_turno' => $this->generarNumeroTurno($especialidad, $consecutivos[$clave]),
                    'estado' => $dato['estado'],
                    'registrado_por' => $registrador->id,
                    'hora_registro' => $horaRegistro,
                    'hora_atencion' => $dato['estado'] === 'atendido' ? $horaRegistro->copy()->addMinutes(20) : null,
                ]
            );
        }
    }

    private function sembrarComunidades(): void
    {
        // Catálogo independiente (todavía sin FK desde Brigada/Paciente, ver sección 6.5 del contexto).
        // Incluye los mismos sectores usados en pacientes/brigadas y dos adicionales para variedad.
        $datos = [
            ['nombre' => 'Cooperativa Suárez', 'sector' => 'Cooperativa Suárez', 'referencia_ubicacion' => 'Frente a la iglesia central'],
            ['nombre' => 'Isla Trinitaria', 'sector' => 'Isla Trinitaria', 'referencia_ubicacion' => 'Junto al mercado de la Coop. 6 de Marzo'],
            ['nombre' => 'Bastión Popular', 'sector' => 'Bastión Popular', 'referencia_ubicacion' => 'Bloque 6, cancha comunal'],
            ['nombre' => 'Monte Sinaí', 'sector' => 'Monte Sinaí', 'referencia_ubicacion' => 'Casa comunal cooperativa 12 de Octubre'],
            ['nombre' => 'Nigeria - Los Vergeles', 'sector' => 'Nigeria - Los Vergeles', 'referencia_ubicacion' => 'Parque central de Los Vergeles'],
            ['nombre' => 'Flor de Bastión', 'sector' => 'Flor de Bastión', 'referencia_ubicacion' => null],
            ['nombre' => 'Guasmo Sur', 'sector' => 'Guasmo Sur', 'referencia_ubicacion' => 'Cooperativa Unión de Bananeros'],
        ];

        foreach ($datos as $dato) {
            Comunidad::firstOrCreate(['nombre' => $dato['nombre']], $dato);
        }
    }

    private function sembrarNoticias(array $usuarios): void
    {
        $coordinadorPrincipal = User::where('email', 'coordinador@brigadasalud.test')->first();
        $coordinadorSecundario = $usuarios['coordinador2@brigadas.com'];

        $datos = [
            [
                'titulo' => 'Nueva jornada médica en Isla Trinitaria',
                'resumen' => 'Atención gratuita en cuatro especialidades durante todo el fin de semana.',
                'contenido' => 'La brigada médica llega a Isla Trinitaria con atención en Medicina General, Pediatría, Odontología y Enfermería. Se recomienda llegar temprano con la cédula a la mano.',
                'imagen_url' => null,
                'fecha_publicacion' => Carbon::now()->subDays(1),
                'autor' => $coordinadorPrincipal,
                'publicada' => true,
            ],
            [
                'titulo' => 'Resultados de la jornada en Bastión Popular',
                'resumen' => 'Más de 30 pacientes atendidos entre Ginecología y Medicina General.',
                'contenido' => 'Gracias a la comunidad y al equipo de brigadistas, la jornada en Bastión Popular cerró con una alta tasa de asistencia y satisfacción de los pacientes atendidos.',
                'imagen_url' => null,
                'fecha_publicacion' => Carbon::now()->subDays(19),
                'autor' => $coordinadorSecundario,
                'publicada' => true,
            ],
            [
                'titulo' => 'Se reprograma la brigada de Nigeria - Los Vergeles',
                'resumen' => 'La jornada se cancela por lluvias y será reprogramada próximamente.',
                'contenido' => 'Por condiciones climáticas adversas, la brigada médica programada en el sector Nigeria - Los Vergeles queda cancelada. Se comunicará la nueva fecha por este mismo medio.',
                'imagen_url' => null,
                'fecha_publicacion' => Carbon::now()->subDays(5),
                'autor' => $coordinadorPrincipal,
                'publicada' => true,
            ],
            [
                'titulo' => 'Borrador: convocatoria de nuevos brigadistas',
                'resumen' => 'Aún en redacción, pendiente de revisión antes de publicar.',
                'contenido' => 'Texto preliminar sobre el proceso de convocatoria para nuevos voluntarios brigadistas. Falta confirmar fechas y requisitos antes de publicar.',
                'imagen_url' => null,
                'fecha_publicacion' => Carbon::now()->addDays(7),
                'autor' => $coordinadorSecundario,
                'publicada' => false,
            ],
        ];

        foreach ($datos as $dato) {
            Noticia::firstOrCreate(
                ['titulo' => $dato['titulo']],
                [
                    'resumen' => $dato['resumen'],
                    'contenido' => $dato['contenido'],
                    'imagen_url' => $dato['imagen_url'],
                    'fecha_publicacion' => $dato['fecha_publicacion'],
                    'autor_id' => $dato['autor']->id,
                    'publicada' => $dato['publicada'],
                ]
            );
        }
    }

    private function sembrarSolicitudesBrigada(array $brigadas): void
    {
        $coordinadorPrincipal = User::where('email', 'coordinador@brigadasalud.test')->first();

        $datos = [
            [
                'nombre_solicitante' => 'Rosa Elena Vera',
                'telefono_contacto' => '0991234501',
                'sector' => 'Guasmo Sur',
                'especialidades_solicitadas' => 'Medicina General, Odontología',
                'motivo' => 'La comunidad no ha recibido atención médica en más de un año.',
                'estado' => 'pendiente',
                'brigada_id' => null,
                'notas_coordinador' => null,
                'gestionado_por' => null,
            ],
            [
                'nombre_solicitante' => 'Manuel Antonio Reyes',
                'telefono_contacto' => '0991234502',
                'sector' => 'Flor de Bastión',
                'especialidades_solicitadas' => 'Pediatría',
                'motivo' => 'Muchos niños del sector necesitan control pediátrico.',
                'estado' => 'en_revision',
                'brigada_id' => null,
                'notas_coordinador' => 'Evaluando disponibilidad de pediatras para el próximo mes.',
                'gestionado_por' => $coordinadorPrincipal->id,
            ],
            [
                'nombre_solicitante' => 'Digna Esperanza Cedeño',
                'telefono_contacto' => '0991234503',
                'sector' => 'Isla Trinitaria',
                'especialidades_solicitadas' => 'Medicina General, Enfermería',
                'motivo' => 'Solicitud del comité barrial para reforzar la jornada ya planificada.',
                'estado' => 'aprobada',
                'brigada_id' => $brigadas['Brigada Isla Trinitaria']->id,
                'notas_coordinador' => 'Aprobada y vinculada a la brigada ya programada en el sector.',
                'gestionado_por' => $coordinadorPrincipal->id,
            ],
            [
                'nombre_solicitante' => 'Franklin Iván Zambrano',
                'telefono_contacto' => '0991234504',
                'sector' => 'Nigeria - Los Vergeles',
                'especialidades_solicitadas' => null,
                'motivo' => 'Solicitud duplicada de una brigada ya cancelada.',
                'estado' => 'rechazada',
                'brigada_id' => null,
                'notas_coordinador' => 'Ya existe una brigada cancelada para este sector, pendiente de reprogramación.',
                'gestionado_por' => $coordinadorPrincipal->id,
            ],
        ];

        foreach ($datos as $dato) {
            SolicitudBrigada::firstOrCreate(
                ['nombre_solicitante' => $dato['nombre_solicitante'], 'sector' => $dato['sector']],
                [
                    'telefono_contacto' => $dato['telefono_contacto'],
                    'especialidades_solicitadas' => $dato['especialidades_solicitadas'],
                    'motivo' => $dato['motivo'],
                    'estado' => $dato['estado'],
                    'brigada_id' => $dato['brigada_id'],
                    'notas_coordinador' => $dato['notas_coordinador'],
                    'gestionado_por' => $dato['gestionado_por'],
                ]
            );
        }
    }

    // Misma regla de generación de prefijo que App\Http\Controllers\Api\TurnoController::generarNumeroTurno().
    private function generarNumeroTurno(Especialidad $especialidad, int $consecutivo): string
    {
        $palabras = preg_split('/\s+/', trim($especialidad->nombre));

        $prefijo = count($palabras) > 1
            ? mb_substr(collect($palabras)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode(''), 0, 3)
            : mb_strtoupper(mb_substr($palabras[0], 0, 3));

        return $prefijo . '-' . str_pad((string) $consecutivo, 3, '0', STR_PAD_LEFT);
    }
}
