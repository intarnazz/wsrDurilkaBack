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
use App\Models\Chat;

Route::post("/message", function (Request $request) {
  $msg = Http::post("https://api.aicloud.sbercloud.ru/public/v2/boltalka/predict", [
    'instances' => $request->instances
  ]);
  return response($msg)->setStatusCode(200);
});

Route::post("/reg", function (Request $request) {
  if (User::all()->where('login', $request->login)->first()) {
    return response()->json(['message' => "Этот логин уже занят"])->setStatusCode(401);
  }
  $user = new User();
  $user->login = $request->login;
  $user->password = $request->password;
  $user->save();
  $user->remember_token = $user->createToken('remember_token')->plainTextToken;
  $user->save();

  return response()->json($user->remember_token)->setStatusCode(200);
});

Route::post("/login", function (Request $request) {
  $user = User::all()->where('login', $request->login)->first();
  if (!$user) {
    return response()->json(['message' => 'Такого логина нет'])->setStatusCode(401);
  }
  if (!($request->password == $user->password)) {
    return response()->json(['message' => 'Неверный пароль'])->setStatusCode(401);
  }
  return response()->json(['token' => $user->remember_token])->setStatusCode(200);
});

Route::post("/loginToken", function (Request $request) {
  $token = $request->header('Authorization');
  $token = str_replace('Bearer ', '', $token);
  $user = User::all()->where('remember_token', $token)->first();
  if (!$user) {
    return response()->json(['message' => 'Неизвестный токен'])->setStatusCode(401);
  }
  return response()->json($user)->setStatusCode(200);
});

Route::post("/chatSet", function (Request $request) {
  $token = $request->header('Authorization');
  $token = str_replace('Bearer ', '', $token);
  $user = User::all()->where('remember_token', $token)->first();
  if (!$user) {
    return response()->json(['message' => 'Неизвестный токен'])->setStatusCode(401);
  }
  $chat = new Chat();
  $chat->user_id = $user->user_id;
  $chat->chatNum = 1;
  $chat->contexts = $request->contextsStr;
  $chat->request = $request->requestStr;
  $chat->save();

  return response()->json($user)->setStatusCode(200);
});

Route::post("/chatGet", function (Request $request) {
  $token = $request->header('Authorization');
  $token = str_replace('Bearer ', '', $token);
  $user = User::all()->where('remember_token', $token)->first();
  if (!$user) {
    return response()->json(['message' => 'Неизвестный токен'])->setStatusCode(401);
  }

  $chat = Chat::all()->where("chatNum", $request->chatNum)->where("user_id", $user->user_id);
  $res = [];
  foreach ($chat as $msg) {
    $res[] = [
      'contexts' => [$msg->contexts],
      'request' => [$msg->request],
    ];
  }
  return response()->json($res)->setStatusCode(200);
});
