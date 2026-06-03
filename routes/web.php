<?php

use App\Models\CollectedBag;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sandbox', function () {
    $user = User::where('email', 'cajera.tienda4@sdi.com')->first();

    // 2. Si no existe, la instanciamos con asignación explícita de propiedades
    if (! $user) {
        $user = new User();
        $user->name = 'Cajera de Pruebas Store 4';
        $user->email = 'cajera.tienda4@sdi.com';
        $user->password = bcrypt('password_secreto');
        $user->store_id = 4; // <-- Asignación explícita inquebrantable
        $user->save();
    }

    Auth::login($user);

    return view('sandbox');
})->middleware(['web']); // Asegura que maneje cookies y sesiones
Route::get('/sandbox-vault', function () {
    $user = User::where('email', 'cajera.tienda4@sdi.com')->first();

    // 2. Si no existe, la instanciamos con asignación explícita de propiedades
    if (! $user) {
        $user = new User();
        $user->name = 'Cajera de Pruebas Store 4';
        $user->email = 'cajera.tienda4@sdi.com';
        $user->password = bcrypt('password_secreto');
        $user->store_id = 4; // <-- Asignación explícita inquebrantable
        $user->save();
    }
    // 2. Login de sesión web para superar el handshake de Reverb (/broadcasting/auth)
    Auth::login($user);

    // 3. Renderizamos la nueva vista dedicada
    return view('sandbox-vault');
})->middleware(['web']);

Route::get('/sandbox-reconcile', function () {
    $user = User::where('email', 'cajera.tienda4@sdi.com')->first();
    if (! $user) {
        $user = new User();
        $user->name = 'Cajera de Pruebas Store 4';
        $user->email = 'cajera.tienda4@sdi.com';
        $user->password = bcrypt('password_secreto');
        $user->store_id = 4; // <-- Asignación explícita inquebrantable
        $user->save();
    }
    Auth::login($user);
    $bags = CollectedBag::query()
        ->whereHas('collectionStop', function ($query) {
            $query->where('store_id', 4);
        })
        ->orderBy('created_at', 'desc')
        ->get();
    return view('sandbox-reconcile', ['initialBags' => $bags]);
});
