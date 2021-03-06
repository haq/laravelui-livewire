<?php

namespace App\Http\Livewire\Auth\Passwords;

use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Component;

class Reset extends Component
{
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $passwordConfirmation = '';

    protected array $rules = [
        'token' => 'required|string',
        'email' => 'required|string|email',
        'password' => 'required|string|min:8|same:passwordConfirmation',
    ];

    public function mount($token)
    {
        $this->email = request()->query('email', '');
        $this->token = $token;
    }

    public function render()
    {
        return view('livewire.auth.passwords.reset')->layout('layouts.guest');
    }

    public function resetPassword()
    {
        $this->validate();

        $response = $this->broker()->reset(
            [
                'token' => $this->token,
                'email' => $this->email,
                'password' => $this->password
            ],
            function ($user, $password) {
                $user->password = Hash::make($password);

                $user->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));

                $this->guard()->login($user);
            }
        );

        if ($response == Password::PASSWORD_RESET) {
            //session()->flash(trans($response));
            return redirect(RouteServiceProvider::HOME);
        }

        $this->addError('email', trans($response));
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return PasswordBroker
     */
    public function broker()
    {
        return Password::broker();
    }

    /**
     * Get the guard to be used during password reset.
     *
     * @return StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard();
    }
}
