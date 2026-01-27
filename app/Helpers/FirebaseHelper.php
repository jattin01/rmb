<?php

namespace App\Helpers;

use Carbon\Carbon;
use Http;
use Google\Client;
use Google\Auth\Credentials\ServiceAccountCredentials;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class FirebaseHelper
{
    public static function getOauthAccessToken(): string
    {
        $serviceAccountPath = base_path(config('firebase.projects.app.credentials'));
        $scopes = ['https://www.googleapis.com/auth/cloud-platform'];
        $credentials = new ServiceAccountCredentials($scopes, $serviceAccountPath);
        $token = $credentials -> fetchAuthToken();
        return $token['access_token'];
    }

}
