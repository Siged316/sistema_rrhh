<?php

namespace App\Providers;

 use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        
       Gate::define('ver-informes-index', function ($user) {
          // 1. Verificamos si el usuario tiene una relación 'rol' y un campo 'nombre'
          // Accedemos al nombre del rol: $user->rol->nombre
          $nombreRol = $user->rol->nombre ?? ''; 
    
          // 2. Normalizamos (convertimos a minúsculas y limpiamos)
          $rolNormalizado = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nombreRol));
    
          // 3. Comparamos
           return $user->isAdmin() || $rolNormalizado === 'gth';
        });
    }

    
}
