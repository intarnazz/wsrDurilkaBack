<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use App\Http\Controllers\durilcaController;
use Illuminate\Support\Facades\Http;
use App\Models\User;

Route::post("/message", function (Request $request) {
  $msg = Http::post("https://api.aicloud.sbercloud.ru/public/v2/boltalka/predict", [
    'instances' => $request->instances
  ]);
  return response($msg);
});

Route::post("/reg", function (Request $request) {
  if (User::all()->where('login', $request->login)->first()) {
    return response()->json(['message' => "Этот логин уже занят"]);
  }
  $user = new User();
  $user->login = $request->login;
  $user->password = $request->password;
  $user->save();
  $user->remember_token = $user->createToken('remember_token')->plainTextToken;
  $user->save();

  return response()->json($user->remember_token);
});

Route::post("/login", function (Request $request) {
  $user = User::all()->where('login', $request->login)->first();
  if (!$user) {
    return response()->json(['message' => 'Такого логина нет']);
  }
  if (!($request->password == $user->password)) {
    return response()->json(['message' => 'Неверный пароль']);
  }
  return response()->json(['token' => $user->remember_token]);
});

Route::post("/loginToken", function (Request $request) {
  $user = User::all()->where('remember_token', $request->token)->first();
  if (!$user) {
    return response()->json(['message' => 'Неизвестный токен']);
  }
  return response()->json($user);
});
