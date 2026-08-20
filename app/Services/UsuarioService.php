<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioService
{
    public function crearUsuario(
        int $gestionadoPorId,
        string $nombre,
        string $email,
        string $password,
        array $rolesCodigos,
        bool $activo = true
    ): User {
        return DB::transaction(function () use (
            $gestionadoPorId,
            $nombre,
            $email,
            $password,
            $rolesCodigos,
            $activo
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
                'nombre' => ['required', 'string', 'max:255'],
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

            $usuario = User::create([
                'name' => $nombre,
                'email' => $email,
                'password' => $password,
                'activo' => $activo,
                'ultimo_acceso' => null,
            ]);

            $usuario->roles()->sync(
                $roles->pluck('id')->all()
            );

            return $usuario->fresh([
                'roles',
            ]);
        }, 3);
    }

    public function actualizarRoles(
        int $usuarioId,
        array $rolesCodigos,
        int $gestionadoPorId
    ): User {
        return DB::transaction(function () use (
            $usuarioId,
            $rolesCodigos,
            $gestionadoPorId
        ) {
            $this->obtenerGestorAutorizado(
                $gestionadoPorId
            );

            $usuario = User::query()
                ->lockForUpdate()
                ->find($usuarioId);

            if (!$usuario) {
                throw new ReglaNegocioException(
                    'El usuario no existe.'
                );
            }

            $roles = $this->obtenerRolesActivos(
                $rolesCodigos
            );

            $usuario->roles()->sync(
                $roles->pluck('id')->all()
            );

            return $usuario->fresh([
                'roles',
            ]);
        }, 3);
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
            $this->obtenerGestorAutorizado(
                $gestionadoPorId
            );

            if (
                !$activo &&
                $usuarioId === $gestionadoPorId
            ) {
                throw new ReglaNegocioException(
                    'Un usuario no puede desactivar su propia cuenta.'
                );
            }

            $usuario = User::query()
                ->lockForUpdate()
                ->find($usuarioId);

            if (!$usuario) {
                throw new ReglaNegocioException(
                    'El usuario no existe.'
                );
            }

            $usuario->activo = $activo;
            $usuario->save();

            return $usuario->fresh([
                'roles',
            ]);
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
            $this->obtenerGestorAutorizado(
                $gestionadoPorId
            );

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

            return $usuario;
        }, 3);
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
    ) {
        $codigos = collect($codigos)
            ->map(fn ($codigo) => strtoupper(
                trim((string) $codigo)
            ))
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