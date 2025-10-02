<?php

class User extends Model {
    
    // Attributes
    private $id;
    protected $email;
    protected $firstname;
    protected $lastname;
    private $username;
    private $password;
    private $role;
    private $enabled;
    private $confirmed_at;
    private $token;
    private $token_expires_at;
    private $created_at;
    private $updated_at;


    protected static $table = 'users';
    protected static $requiredFields = ['firstname','lastname','email','username','password'];
    protected static $uniqueFields = ['username', 'email'];
    public static $roles = [];

    public static function setRoles() {
        static::$roles = [
            0 => Lang::getText("user:default"),
            1 => Lang::getText("user:moderator"),
            2 => Lang::getText("user:administrator")
        ];
    }

    public static $indexLabels = [];  

    public static function setIndexLabels() {
        static::$indexLabels = [
            'id' => '#',
            'username' => Lang::getText("user:username"),
            'firstname' => Lang::getText("user:firstname"),
            'lastname' => Lang::getText("user:lastname"),
            'email' => Lang::getText("user:mail"),
            'role' => Lang::getText("user:role"),
            'enabled' => Lang::getText("general:enabled"),
        ];
    }

    protected static $captchaUrl = 'https://www.google.com/recaptcha/api/siteverify';
    protected static $captchaKey = '6LfljmMrAAAAAABqk8o2NnPcZZi5HPIcxKygnX3_';


    // Methods
    public static function validateData($data) {
        $errors = static::checkRequiredFields($data);
        if ($data['firstname'] ?? false) {
            if (strlen($data['firstname']) < 2) {
                $errors['firstname'][] = 'Il nome deve essere lungo almeno 3 caratteri.';
            }
            if (!preg_match("/^[\p{L}\p{M}]+(?: [\p{L}\p{M}]+)*(?:['\-][\p{L}\p{M}]+)*(?:(?<!\.)\. ?[\p{L}\p{M}]+)*$/u", $data['firstname'])) {
                $errors['firstname'][] = "Il nome contiene caratteri non ammessi.";
            }
        }

        // Lastname validation
        if ($data['lastname'] ?? false) {
            if (strlen($data['lastname']) < 2) {
                $errors['lastname'][] = "Il cognome deve essere lungo almeno 2 caratteri.";
            } 
            if (!preg_match("/^[\p{L}\p{M}]+(?: [\p{L}\p{M}]+)*(?:['\-][\p{L}\p{M}]+)*(?:(?<!\.)\. ?[\p{L}\p{M}]+)*$/u", $data['lastname'])) {
                $errors['lastname'][] = "Il cognome contiene caratteri non ammessi.";
            }
        }

        // Email validation
        if ($data['email'] ?? false) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'][] = "Formato email non valido.";
            }

            if ($data['id'] ?? false) {
                $users = User::select([
                    "where" => sprintf("id != %d AND email = '%s'", $data['id'], $data['email'])
                ]);
            } else {
                $users = User::select([
                    "where" => sprintf("email = '%s'", $data['email'])
                ]);
            }
            
            if ($users ?? false) {
                $errors['email'][] = "Email già in uso";
            }
        }

        // Username validation
        if ($data['username'] ?? false) {
            if (strlen($data['username']) < 6) {
                $errors['username'][] = "L'username deve essere lungo almeno 6 caratteri."; 
            }
            if (!preg_match('/^[0-9a-zA-Z\.]+$/', $data['username'])) {
                $errors['username'][] = "L'username può contenere solo lettere maiuscole o minuscole, numeri e punti.";
            }
        }

        if ($data['password_score'] ?? false) {
            if ($data['password_score'] < 2) {
                $errors['password'][] = "La password non è sicura";
            }
        }

        if (($data['password'] ?? false) || ($data['confirmation_password'] ?? false)) {
            
            if (!$data['password']) {
                $errors['password'][] = "Inserisci la password";
            }

            if ($data['confirmation_password'] ?? false) {
                if (!$data['confirmation_password']) {
                    $errors['confirmation_password'][] = 'Conferma la password';
                }
                if ($data['password'] !== $data['confirmation_password']) {
                    $errors['password'][] = 'Le password non coincidono';
                } 
    
                if ($data['password'] === $data['confirmation_password']) {
                    $data['plain_password'] = $data['password'];
                }    
            }
        }
        
        return $errors ?: static::checkDataUniqueness($data);
    }

    public static function prepareData($data, $new = false) {
        if ($data['email'] ?? false) $data['email'] = strtolower($data['email']);
        if ($data['firstname'] ?? false) {
            if (str_word_count($data['firstname']) > 1) {
                $data['firstname'] = explode(" ", $data['firstname']);
                $mapped = array_map(function ($item) {
                    return ucfirst(strtolower($item));
                }, $data['firstname']);
                $data['firstname'] = implode(" ", $mapped);
            } else {
                $data['firstname'] = ucfirst(strtolower($data['firstname']));
            }
        }
        if ($data['lastname'] ?? false) {
            if (str_word_count($data['lastname']) > 1) {
                $data['lastname'] = explode(" ", $data['lastname']);
                $mapped = array_map(function ($item) {
                    return ucfirst(strtolower($item));
                }, $data['lastname']);
                $data['lastname'] = implode(" ", $mapped);
            } else {
                $data['lastname'] = ucfirst(strtolower($data['lastname']));
            }
        }
        if ($data['username'] ?? false) $data['username'] = strtolower($data['username']);
        if ($data['password'] ?? false) $data['password'] = sha1(md5($data['password']));
        unset($data['password_score']);
        unset($data['plain_password']);
        unset($data['confirmation_password']);
        $now = date('Y-m-d H:i:s');
        $data['updated_at'] = "$now";

        if ($new) {
            $data['created_at'] = "$now";
            if (!isset($data['role'])) $data['role'] = 0;
            if(!isset($data['enabled'])) $data['enabled'] = 0;
            $data['token'] = bin2hex(random_bytes(32));
            $data['token_expires_at'] = date('Y-m-d H:i:s', strtotime('+48 hours'));
        }
        return $data;
    }

    public static function encryptString($email, $encryptedPassword) {
        return sha1("$email|$encryptedPassword|{$_ENV['SECRETKEYWORD']}");
    }

    // Login function
    public static function login($data) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $errors = [];
        
        $where = "";

        if (!$data['login'] || !$data['password']) {
            if (!$data['login']) {
                $errors['login'] = 'Inserisci username o email.';
            }
            if (!$data['password']) {
                $errors['password'] = 'Inserisci la password.';
            }
            return $errors;
        }

        if ($data['login'] && $data['password']) {
            $where = sprintf("(username = '%1\$s' OR email = '%1\$s') AND password = '%2\$s'", $data['login'], sha1(md5($data['password'])));
        }

        $users = static::select([
            "where" => $where,
            "limit" => 1
        ]);


        if (empty($users)) {
            $errors['general'] = 'Username o password errati';
            return $errors;
        }

        if (!($users[0]['enabled'] ?? false)) {
            $errors['general'] = 'Account non abilitato.';
            return $errors;
        }

        $_SESSION['id'] = $users[0]['id'];

        if (isset($data['remember-me'])) {
            $encryptedString = static::encryptString($users[0]['email'], $users[0]['password']);
            setcookie($_ENV['CREDENTIALS_COOKIE'], $encryptedString, time() + $_ENV['COOKIE_TIME'], $path = '/');
        }

        Log::create([
            [
                "user_id" => $_SESSION['id'],
                "action_type" => "Login",
                "ip_address" => $_SERVER['REMOTE_ADDR'],
                "level" => 0
            ]
        ]);
        return true;
    }

    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        Log::create([
            [
                "user_id" => $_SESSION['id'],
                "action_type" => "Logout",
                "ip_address" => $_SERVER['REMOTE_ADDR'],
                "level" => 0
            ]
        ]);
        $_SESSION = [];
        
        session_destroy();

        if (isset($_COOKIE[$_ENV['CREDENTIALS_COOKIE']])) {
            setcookie($_ENV['CREDENTIALS_COOKIE'], false, time() - 3600, $path = '/');
        }
        
    }
    
    public static function getUserByCookie() {
        
        $user = User::select([
            "where" => sprintf("sha1(CONCAT_WS('|', email, password, '%s')) = '%s'", $_ENV['SECRETKEYWORD'], $_COOKIE[$_ENV['CREDENTIALS_COOKIE']])
        ]);

        if ($user ?? false) {
            return $user;
        }
        return false;
    }

    public static function isAuthenticated() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        
        if ($_SESSION['id'] ?? false) {
            $user = static::select([
            'where' => 'id = ' . $_SESSION['id']
            ]);
        } else if (isset($_COOKIE[$_ENV['CREDENTIALS_COOKIE']])) {
            $user = static::getUserByCookie($_COOKIE[$_ENV['CREDENTIALS_COOKIE']]);
        }
        if ($user ?? false) {
            $_SESSION['id'] = $user[0]['id'];
            $_SESSION['firstname'] = $user[0]['firstname'];
            $_SESSION['lastname'] = $user[0]['lastname'];
            $_SESSION['username'] = $user[0]['username'];
            $_SESSION['email'] = $user[0]['email'];
            $_SESSION['role'] = $user[0]['role'];
            $_SESSION['enabled'] = $user[0]['enabled'];
            
            if ($user[0]['enabled'] === 1) {
                return true;
            }
        }
        return false;
    }

    public static function isAdmin() {
        if (!static::isAuthenticated()) {
            return false;
        }

        if (isset($_SESSION['role']) && $_SESSION['role'] === 2) {
            return true;
        }
        return false;
    }

    public static function isModerator() {
        if (!static::isAuthenticated()) {
            return false;
        }

        if (isset($_SESSION['role']) && in_array($_SESSION['role'], [1, 2])) {
            return true;
        }
        return false;
    }

    public static function requireAuthentication() {
        if (!static::isAuthenticated()) {
            header("Location: /users/login.php");
            exit();
        }
    }

    public static function requireAdmin() {
        if (!static::isAuthenticated()) {
            header("Location: /users/login.php");
            exit();
        } else if (!static::isAdmin()) {
            header("Location: /users/access_denied.php");
            exit();
        }
    }

    public static function requireModerator() {
        if (!static::isAuthenticated()) {
            header("Location: /users/login.php");
            exit();
        } else if (!static::isModerator() && !static::isAdmin()) {
            header("Location: /users/access_denied.php");
            exit();
        }
    }

    public static function prepareAndSendEmail($userEmail, $templateType) {
        $users = User::select([
            "where" => sprintf("email = '%s'", $userEmail)
        ]);

        if ($users ?? false) {
            $user = $users[0];
    
            $tagData = [];
            foreach (Mail_templates::$possibleTags[$templateType] as $tag => $info) {
                switch ($tag) {
                    case 'id':
                    case 'username':
                    case 'firstname':
                    case 'lastname':
                    case 'email':
                        $tagData[$tag] = $user[$tag];
                        break;
                    case 'confirmation_url':
                    case 'reset_password_url':
                        $tagData[$tag] = sprintf('%s/users/%s.php?token=%s', $_ENV['APP_URL'], $templateType, $user['token']);
                        break;
                    case 'footer_disclaimer':
                        $tagData[$tag] = 'Orario di invio della mail: ' . date("Y-m-d H:i:s");
                        break;
                }
            }
            [$subject, $body] = Mail_templates::prepareEmailData($user, $templateType, $tagData);
           
            if ($subject && $body) {
                $sentEmail = Mail::send($user['email'], $subject, $body);
                return $sentEmail;
            }
        }
    }

    public static function registerAndSendConfirmation($userData) {        
        $created = static::create([$userData]);
        if ($created == 1) {
            // Se creato, invia la mail
            $emailSent = static::prepareAndSendEmail($userData['email'], 'confirmation_account');
            
            if ($emailSent['success'] <= 0) {
                static::delete(sprintf("email = '%s'", $userData['email']));
                return [
                    "success" => 0,
                    "message" => "Attenzione: si è verificato un errore, riprova tra qualche minuto. <br>Se il problema persiste, contatta il supporto."
                ];
            }
            $usersId = User::select([
                "columns" => "id",
                "where" => sprintf("email = '%s'", $userData['email'])
            ]);
            Log::create([
                [
                    "user_id" => $usersId[0]['id'],
                    "action_type" => "Registered",
                    "ip_address" => $_SERVER['REMOTE_ADDR'],
                    "level" => 0
                ]
            ]);
            return $emailSent;
        }

        return ['success' => 0, 'message' => $created];
    }

    public static function requestPasswordReset($loginData) {
        $where = sprintf("(username = '%1\$s' OR email = '%1\$s')", $loginData);
        $users = static::select([
            "where" => $where,
            "limit" => 1
        ]);

        if (!$users) return ["success" => 0, "message" => "Nessun account trovato con questi dati."];

        $user = $users[0];

        $token = bin2hex(random_bytes(32));
        $where = "id = " . $user['id'];
        $updatedUser = static::update([
            "token" => $token,
            "token_expires_at" => date('Y-m-d H:i:s', strtotime('+48 hours'))
        ], $where);
        $emailSent = static::prepareAndSendEmail($user['email'], 'reset_password');
        
        if ($emailSent['success'] <= 0) {
            $updatedUser = static::update([
                "token" => null,
                "token_expires_at" => null
            ], $where);
        }
        Log::create([
            [
                "user_id" => $user['id'],
                "action_type" => "Request password reset",
                "ip_address" => $_SERVER['REMOTE_ADDR'],
                "level" => 2
            ]
        ]);
        return $emailSent;
    }

    public static function verifyCaptcha($token) {
        $url = static::$captchaUrl;
        $data = [
            "secret" => static::$captchaKey,
            "response" => $token,
            "remoteip" => $_SERVER['REMOTE_ADDR']
        ];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        $server_output = curl_exec($ch);
        
        return json_decode($server_output);
        
    }
}

