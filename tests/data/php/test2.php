<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Validator;

/**
* Lorem ipsum.
*
* @param  string $param1
* @param  bool   $param2   lorem ipsum
* @param  string $param3   lorem ipsum
* @return int    lorem ipsum
*/
class AuthController extends Controller
{
use AuthenticatesAndRegistersUsers, ThrottlesLogins;
protected $redirectTo = '/home';

public function __construct()
{
$this->middleware('guest', ['except' => 'logout']);
}

/**
* Lorem ipsum.
*
* @param  string $param1
* @param  bool   $param2   lorem ipsum
* @param  string $param3   lorem ipsum
* @return int    lorem ipsum
*/
protected function validator(array $data)
{
return Validator::make($data,
[
'name'     => 'required|max:255',
'email'    => 'required|email|max:255|unique:users',
'password' => 'required|confirmed|min:6',
]
);
}

protected function create(array $data)
{
return User::create([
'name'     => $data['name'],
'email'    => $data['email'],
'password' => bcrypt($data['password']),
]);
}
}
