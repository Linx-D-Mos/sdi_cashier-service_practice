<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sandbox', function () {



    // 1. Buscamos o creamos una cajera de pruebas de forma segura
    $user = User::query()->firstOrCreate(
        ['email' => 'cajera.tienda4@sdi.com'],
        [
            'name' => 'Cajera de Pruebas Store 4',
            'password' => bcrypt('password_secreto'),
            'store_id' => 4, // <-- CLAVE: Mismo ID que procesa tu Kafka Handler
        ]
    );

    // 2. Autenticamos al usuario en la sesión de Laravel inmediatamente
    Auth::login($user);

    // 3. Retornamos la vista con la sesión ya inyectada
    return view('sandbox');
})->middleware(['web']); // Asegura que maneje cookies y sesiones
