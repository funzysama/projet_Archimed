<?php


namespace App\Controller;


use App\Service\PasswordGenerator;
use Symfony\Component\Routing\Annotation\Route;

class TestController
{

    /**
     * @Route("/test", name="test")
     */
    public function test(PasswordGenerator $passwordGenerator){
        $pass = $passwordGenerator->generateRandomStrongPassword(34);
        dd($pass);
    }
}