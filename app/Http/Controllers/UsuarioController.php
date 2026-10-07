<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\Auditoria;
use App\Models\Rol;
use App\Models\User;
use App\Services\UsuarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function __construct(
        private readonly UsuarioService $usuarioService
    ) {
    }

    public function index(Request $request): View
    {
        $busqueda = trim(
            (string) $request->query('buscar', '')
        );

        $estado = trim(
            (string) $request->query('estado', '')
        );

        $rol = trim(
            (string) $request->query('rol', '')
        );

        $sede = trim(
            (string) $request->query('sede', '')
        );

        $usuarios = User::query()
            ->with([
                'roles' => fn ($query) =>
                    $query->orderBy('nombre'),
                'almacenOperativo',
            ])
            ->when(
                $busqueda !== '',
                function ($query) use ($busqueda) {
                    $query->where(function ($subquery) use ($busqueda) {
                        $subquery
                            ->where(
                                'name',
                                'like',
                                "%{$busqueda}%"
                            )
                            ->orWhere(
                                'email',
                                'like',
                                "%{$busqueda}%"
                            );
                    });
                }
            )
            ->when(
                $estado === 'activo',
                fn ($query) =>
                    $query->where('activo', true)
            )
            ->when(
                $estado === 'inactivo',
                fn ($query) =>
                    $query->where('activo', false)
            )
            ->when(
                $rol !== '',
                fn ($query) =>
                    $query->whereHas(
                        'roles',
                        fn ($rolesQuery) =>
                            $rolesQuery->where(
                                'roles.codigo',
                                $rol
                            )
                    )
            )
            ->when(
                $sede === 'sin_sede',
                fn ($query) =>
                    $query->whereNull(
                        'almacen_operativo_id'
                    )
            )
            ->when(
                $sede !== ''
                && $sede !== 'sin_sede',
                fn ($query) =>
                    $query->where(
                        'almacen_operativo_id',
                        (int) $sede
                    )
            )
            ->orderByDesc('activo')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $roles = Rol::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $almacenes = Almacen::query()
            ->where('activo', true)
            ->orderByDesc('principal')
            ->orderBy('nombre')
            ->get();

        $resumen = [
            'total' => User::query()->count(),
            'activos' => User::query()
                ->where('activo', true)
                ->count(),
            'inactivos' => User::query()
                ->where('activo', false)
                ->count(),
            'sede_pendiente' => User::query()
                ->where('activo', true)
                ->whereNull('almacen_operativo_id')
                ->whereDoesntHave(
                    'roles',
                    fn ($query) =>
                        $query->where(
                            'roles.codigo',
                            'ADMINISTRADOR'
                        )
                )
                ->count(),
        ];

        return view(
            'usuarios.index',
            compact(
                'usuarios',
                'roles',
                'almacenes',
                'resumen',
                'busqueda',
                'estado',
                'rol',
                'sede'
            )
        );
    }

    public function create(): View
    {
        return view(
            'usuarios.create',
            [
                'roles' => Rol::query()
                    ->where('activo', true)
                    ->orderBy('nombre')
                    ->get(),

                'almacenes' => Almacen::query()
                    ->where('activo', true)
                    ->orderByDesc('principal')
                    ->orderBy('nombre')
                    ->get(),
            ]
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $datos = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
            'roles' => [
                'required',
                'array',
                'min:1',
            ],
            'roles.*' => [
                'required',
                'string',
                'max:100',
            ],
            'almacen_operativo_id' => [
                'nullable',
                'integer',
            ],
            'activo' => [
                'nullable',
                'boolean',
            ],
        ]);

        try {
            $usuario = $this
                ->usuarioService
                ->crearUsuario(
                    gestionadoPorId:
                        (int) $request->user()->id,

                    nombre:
                        $datos['name'],

                    email:
                        $datos['email'],

                    password:
                        $datos['password'],

                    rolesCodigos:
                        $datos['roles'],

                    activo:
                        $request->boolean('activo'),

                    almacenOperativoId:
                        isset($datos['almacen_operativo_id'])
                            ? (int) $datos['almacen_operativo_id']
                            : null
                );
        } catch (ReglaNegocioException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'usuario' =>
                        $exception->getMessage(),
                ]);
        }

        return redirect()
            ->route(
                'usuarios.show',
                $usuario
            )
            ->with(
                'success',
                'Usuario creado correctamente.'
            );
    }

    public function show(
        Request $request,
        User $usuario
    ): View {
        $usuario->load([
            'roles',
            'almacenOperativo',
        ]);

        $auditorias = collect();

        if (
            $request->user()
                ?->tienePermiso('auditoria.ver')
        ) {
            $auditorias = Auditoria::query()
                ->with('usuario')
                ->where('entidad', 'User')
                ->where(
                    'entidad_id',
                    $usuario->id
                )
                ->recientes()
                ->limit(10)
                ->get();
        }

        return view(
            'usuarios.show',
            [
                'usuario' => $usuario,

                'roles' => Rol::query()
                    ->where('activo', true)
                    ->orderBy('nombre')
                    ->get(),

                'almacenes' => Almacen::query()
                    ->where('activo', true)
                    ->orderByDesc('principal')
                    ->orderBy('nombre')
                    ->get(),

                'auditorias' => $auditorias,
            ]
        );
    }

    public function update(
        Request $request,
        User $usuario
    ): RedirectResponse {
        $datos = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
            ],
            'roles' => [
                'required',
                'array',
                'min:1',
            ],
            'roles.*' => [
                'required',
                'string',
                'max:100',
            ],
            'almacen_operativo_id' => [
                'nullable',
                'integer',
            ],
        ]);

        try {
            $usuario = $this
                ->usuarioService
                ->actualizarUsuario(
                    usuarioId:
                        $usuario->id,

                    nombre:
                        $datos['name'],

                    email:
                        $datos['email'],

                    rolesCodigos:
                        $datos['roles'],

                    almacenOperativoId:
                        isset($datos['almacen_operativo_id'])
                            ? (int) $datos['almacen_operativo_id']
                            : null,

                    gestionadoPorId:
                        (int) $request->user()->id
                );
        } catch (ReglaNegocioException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'usuario' =>
                        $exception->getMessage(),
                ]);
        }

        return redirect()
            ->route(
                'usuarios.show',
                $usuario
            )
            ->with(
                'success',
                'Acceso, roles y sede actualizados correctamente.'
            );
    }

    public function estado(
        Request $request,
        User $usuario
    ): RedirectResponse {
        $datos = $request->validate([
            'activo' => [
                'required',
                'boolean',
            ],
        ]);

        try {
            $usuario = $this
                ->usuarioService
                ->cambiarEstado(
                    usuarioId:
                        $usuario->id,

                    activo:
                        (bool) $datos['activo'],

                    gestionadoPorId:
                        (int) $request->user()->id
                );
        } catch (ReglaNegocioException $exception) {
            return back()->withErrors([
                'usuario' =>
                    $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route(
                'usuarios.show',
                $usuario
            )
            ->with(
                'success',
                $usuario->activo
                    ? 'Usuario activado correctamente.'
                    : 'Usuario desactivado correctamente.'
            );
    }

    public function contrasena(
        Request $request,
        User $usuario
    ): RedirectResponse {
        $datos = $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        try {
            $this
                ->usuarioService
                ->restablecerContrasena(
                    usuarioId:
                        $usuario->id,

                    nuevaContrasena:
                        $datos['password'],

                    gestionadoPorId:
                        (int) $request->user()->id
                );
        } catch (ReglaNegocioException $exception) {
            return back()->withErrors([
                'usuario' =>
                    $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route(
                'usuarios.show',
                $usuario
            )
            ->with(
                'success',
                'Contraseña restablecida correctamente.'
            );
    }
}
