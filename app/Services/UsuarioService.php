<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioService
{
    public function __construct(
        private readonly AuditoriaService $auditoriaService
    ) {
    }

    public function crearUsuario(
        int $gestionadoPorId,
        string $nombre,
        string $email,
        string $password,
        array $rolesCodigos,
        bool $activo = true,
        ?int $almacenOperativoId = null
    ): User {
        return DB::transaction(function () use (
            $gestionadoPorId,
            $nombre,
            $email,
            $password,
            $rolesCodigos,
            $activo,
            $almacenOperativoId
        ) {
            $gestor = $this->obtenerGestorAutorizado(
                $gestionadoPorId
            );

            $nombre = trim($nombre);
            $email = mb_strtolower(trim($email));

            Validator::make([
                'nombre' => $nombre,
                'email' => $email,
                'password' => $password,
            ], [
                'nombre' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email'),
                ],
                'password' => [
                    'required',
                    Password::min(8),
                ],
            ])->validate();

            $roles = $this->obtenerRolesActivos(
                $rolesCodigos
            );

            $almacen = $this->resolverAlmacenOperativo(
                $roles,
                $almacenOperativoId
            );

            $usuario = User::create([
                'name' => $nombre,
                'email' => $email,
                'password' => $password,
                'activo' => $activo,
                'ultimo_acceso' => null,
                'almacen_operativo_id' => $almacen?->id,
            ]);

            $usuario->roles()->sync(
                $roles->pluck('id')->all()
            );

            $this->auditoriaService->registrar(
                usuarioId: $gestor->id,
                accion: 'CREAR_USUARIO',
                entidad: 'User',
                entidadId: $usuario->id,
                datosAnteriores: null,
                datosNuevos: [
                    'nombre' => $usuario->name,
                    'email' => $usuario->email,
                    'activo' => $usuario->activo,
                    'roles' => $roles->pluck('codigo')->values()->all(),
                    'almacen_operativo_id' => $almacen?->id,
                ]
            );

            return $usuario->fresh([
                'roles',
                'almacenOperativo',
            ]);
        }, 3);
    }

    public function actualizarUsuario(
        int $usuarioId,
        string $nombre,
        string $email,
        array $rolesCodigos,
        ?int $almacenOperativoId,
        int $gestionadoPorId
    ): User {
        return DB::transaction(function () use (
            $usuarioId,
            $nombre,
            $email,
            $rolesCodigos,
            $almacenOperativoId,
            $gestionadoPorId
        ) {
            $gestor = $this->obtenerGestorAutorizado(
                $gestionadoPorId
            );

            if ($usuarioId === $gestionadoPorId) {
                throw new ReglaNegocioException(
                    'Los roles y la sede de la cuenta actual no pueden modificarse desde Administración. Use Perfil para actualizar sus datos personales.'
                );
            }

            $usuario = User::query()
                ->with([
                    'roles',
                    'almacenOperativo',
                ])
                ->lockForUpdate()
                ->find($usuarioId);

            if (!$usuario) {
                throw new ReglaNegocioException(
                    'El usuario no existe.'
                );
            }

            $nombre = trim($nombre);
            $email = mb_strtolower(trim($email));

            Validator::make([
                'nombre' => $nombre,
                'email' => $email,
            ], [
                'nombre' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')
                        ->ignore($usuario->id),
                ],
            ])->validate();

            $roles = $this->obtenerRolesActivos(
                $rolesCodigos
            );

            $almacen = $this->resolverAlmacenOperativo(
                $roles,
                $almacenOperativoId
            );

            $this->protegerAdministradorGlobal(
                $usuario,
                seguiraActivo: $usuario->activo,
                seguiraAdministrador: $roles->contains(
                    'codigo',
                    'ADMINISTRADOR'
                )
            );

            $anteriores = $this->snapshotUsuario(
                $usuario
            );

            $usuario->name = $nombre;
            $usuario->email = $email;
            $usuario->almacen_operativo_id = $almacen?->id;
            $usuario->save();

            $usuario->roles()->sync(
                $roles->pluck('id')->all()
            );

            $usuario = $usuario->fresh([
                'roles',
                'almacenOperativo',
            ]);

            $this->auditoriaService->registrar(
                usuarioId: $gestor->id,
                accion: 'ACTUALIZAR_ACCESO_USUARIO',
                entidad: 'User',
                entidadId: $usuario->id,
                datosAnteriores: $anteriores,
                datosNuevos: $this->snapshotUsuario(
                    $usuario
                )
            );

            return $usuario;
        }, 3);
    }

    public function actualizarRoles(
        int $usuarioId,
        array $rolesCodigos,
        int $gestionadoPorId,
        ?int $almacenOperativoId = null
    ): User {
        $usuario = User::query()->find($usuarioId);

        if (!$usuario) {
            throw new ReglaNegocioException(
                'El usuario no existe.'
            );
        }

        return $this->actualizarUsuario(
            usuarioId: $usuario->id,
            nombre: $usuario->name,
            email: $usuario->email,
            rolesCodigos: $rolesCodigos,
            almacenOperativoId:
                $almacenOperativoId
                ?? $usuario->almacen_operativo_id,
            gestionadoPorId: $gestionadoPorId
        );
    }

    public function cambiarEstado(
        int $usuarioId,
        bool $activo,
        int $gestionadoPorId
    ): User {
        return DB::transaction(function () use (
            $usuarioId,
            $activo,
            $gestionadoPorId
        ) {
            $gestor = $this->obtenerGestorAutorizado(
                $gestionadoPorId
            );

            if (
                !$activo
                && $usuarioId === $gestionadoPorId
            ) {
                throw new ReglaNegocioException(
                    'Un usuario no puede desactivar su propia cuenta.'
                );
            }

            $usuario = User::query()
                ->with([
                    'roles',
                    'almacenOperativo',
                ])
                ->lockForUpdate()
                ->find($usuarioId);

            if (!$usuario) {
                throw new ReglaNegocioException(
                    'El usuario no existe.'
                );
            }

            $this->protegerAdministradorGlobal(
                $usuario,
                seguiraActivo: $activo,
                seguiraAdministrador:
                    $usuario->tieneRol('ADMINISTRADOR')
            );

            $anteriores = $this->snapshotUsuario(
                $usuario
            );

            $usuario->activo = $activo;
            $usuario->save();

            $usuario = $usuario->fresh([
                'roles',
                'almacenOperativo',
            ]);

            $this->auditoriaService->registrar(
                usuarioId: $gestor->id,
                accion: $activo
                    ? 'ACTIVAR_USUARIO'
                    : 'DESACTIVAR_USUARIO',
                entidad: 'User',
                entidadId: $usuario->id,
                datosAnteriores: $anteriores,
                datosNuevos: $this->snapshotUsuario(
                    $usuario
                )
            );

            return $usuario;
        }, 3);
    }

    public function restablecerContrasena(
        int $usuarioId,
        string $nuevaContrasena,
        int $gestionadoPorId
    ): User {
        return DB::transaction(function () use (
            $usuarioId,
            $nuevaContrasena,
            $gestionadoPorId
        ) {
            $gestor = $this->obtenerGestorAutorizado(
                $gestionadoPorId
            );

            if ($usuarioId === $gestionadoPorId) {
                throw new ReglaNegocioException(
                    'Use Perfil para cambiar la contraseña de su propia cuenta.'
                );
            }

            Validator::make([
                'password' => $nuevaContrasena,
            ], [
                'password' => [
                    'required',
                    Password::min(8),
                ],
            ])->validate();

            $usuario = User::query()
                ->lockForUpdate()
                ->find($usuarioId);

            if (!$usuario) {
                throw new ReglaNegocioException(
                    'El usuario no existe.'
                );
            }

            $usuario->password = $nuevaContrasena;
            $usuario->save();

            $this->auditoriaService->registrar(
                usuarioId: $gestor->id,
                accion: 'RESTABLECER_CONTRASENA_USUARIO',
                entidad: 'User',
                entidadId: $usuario->id,
                datosAnteriores: null,
                datosNuevos: [
                    'contrasena_restablecida' => true,
                ]
            );

            return $usuario;
        }, 3);
    }

    private function resolverAlmacenOperativo(
        Collection $roles,
        ?int $almacenOperativoId
    ): ?Almacen {
        $esAdministradorGlobal =
            $roles->contains(
                'codigo',
                'ADMINISTRADOR'
            );

        if ($almacenOperativoId === null) {
            if (!$esAdministradorGlobal) {
                throw new ReglaNegocioException(
                    'Debe asignarse una sede operativa al usuario.'
                );
            }

            return null;
        }

        $almacen = Almacen::query()
            ->where('activo', true)
            ->find($almacenOperativoId);

        if (!$almacen) {
            throw new ReglaNegocioException(
                'La sede operativa seleccionada no existe o se encuentra inactiva.'
            );
        }

        return $almacen;
    }

    private function protegerAdministradorGlobal(
        User $usuario,
        bool $seguiraActivo,
        bool $seguiraAdministrador
    ): void {
        if (
            !$usuario->activo
            || !$usuario->tieneRol('ADMINISTRADOR')
            || (
                $seguiraActivo
                && $seguiraAdministrador
            )
        ) {
            return;
        }

        $existeOtroAdministrador =
            User::query()
                ->where('activo', true)
                ->where(
                    'id',
                    '!=',
                    $usuario->id
                )
                ->whereHas(
                    'roles',
                    fn ($query) =>
                        $query
                            ->where(
                                'roles.codigo',
                                'ADMINISTRADOR'
                            )
                            ->where(
                                'roles.activo',
                                true
                            )
                )
                ->exists();

        if (!$existeOtroAdministrador) {
            throw new ReglaNegocioException(
                'Debe permanecer al menos un administrador global activo.'
            );
        }
    }

    private function snapshotUsuario(
        User $usuario
    ): array {
        if (!$usuario->relationLoaded('roles')) {
            $usuario->load('roles');
        }

        return [
            'nombre' => $usuario->name,
            'email' => $usuario->email,
            'activo' => (bool) $usuario->activo,
            'roles' => $usuario->roles
                ->pluck('codigo')
                ->sort()
                ->values()
                ->all(),
            'almacen_operativo_id' =>
                $usuario->almacen_operativo_id,
        ];
    }

    private function obtenerGestorAutorizado(
        int $usuarioId
    ): User {
        $usuario = User::query()
            ->where('activo', true)
            ->find($usuarioId);

        if (!$usuario) {
            throw new ReglaNegocioException(
                'El usuario gestor no existe o se encuentra inactivo.'
            );
        }

        if (!$usuario->tienePermiso('usuarios.gestionar')) {
            throw new ReglaNegocioException(
                'El usuario no cuenta con permiso para gestionar usuarios.'
            );
        }

        return $usuario;
    }

    private function obtenerRolesActivos(
        array $codigos
    ): Collection {
        $codigos = collect($codigos)
            ->map(
                fn ($codigo) =>
                    strtoupper(
                        trim((string) $codigo)
                    )
            )
            ->filter()
            ->unique()
            ->values();

        if ($codigos->isEmpty()) {
            throw new ReglaNegocioException(
                'Debe asignarse al menos un rol.'
            );
        }

        $roles = Rol::query()
            ->where('activo', true)
            ->whereIn('codigo', $codigos)
            ->get();

        if ($roles->count() !== $codigos->count()) {
            throw new ReglaNegocioException(
                'Uno o más roles no existen o se encuentran inactivos.'
            );
        }

        return $roles;
    }
}