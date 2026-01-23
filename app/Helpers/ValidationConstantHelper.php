<?php

namespace App\Helpers;

use Dotenv\Util\Regex;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ValidationConstantHelper
{
    const MIN_PHONE_NO_DIGITS = 10;
    const MAX_PHONE_NO_DIGITS = 10;
    const CAPACITY_MIN_LIMIT = 50;
    const CAPACITY_MAX_LIMIT = 200;
    const MAX_USERNAME_LIMIT = 30;
    const DEFAULT_CHARACTER_LIMIT = 255;
    const MIN_PASSWORD_LIMIT = 8;
    const MAX_PASSWORD_LIMIT = 20;
    const MAX_LAT_LNG_LENGTH = 40;
    const REGEX_MOBILE_NUMBER = '/^[1-9][0-9]*$/';
    const REGEX_EMAIL = '/^[\w\.-]+@[\w\.-]+\.[a-zA-Z]{2,6}$/';
}
