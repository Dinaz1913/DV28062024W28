<?php

namespace Reelz222z\Cryptoexchange\Controller;

use Symfony\Component\HttpFoundation\Request;
use Reelz222z\Cryptoexchange\Model\Login;
use Reelz222z\Cryptoexchange\Model\User;

class AuthController
{
    public function login(Request $request)
    {
        if ($request->isMethod('POST')) {
            $username = $request->request->get('username');
            $password = $request->request->get('password');
            try {
                $user = Login::authenticate($username, $password);
                if ($user instanceof User) {
                    return $user;
                }
                return 'Invalid username or password.';
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        }
        return null;
    }
}
