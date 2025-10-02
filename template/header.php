<?php include_once __DIR__ . '/../config.php'?>
<?php
$captchaURi = [
    '/users/register.php',
    '/users/login.php',
    '/users/login.php?registration_status=success'
];

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$checkPasswordURi = [
    '/users/register.php',
    '/users_update',
    '/profile'
];

$isUsersUpdate = str_starts_with($currentPath, '/users_update');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $pageTitle . ' - ' . $_ENV['APP_NAME'] ?>
    </title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cal+Sans&family=Courier+Prime:ital,wght@0,400;0,700;1,400;1,700&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cal+Sans&family=Courier+Prime:ital,wght@0,400;0,700;1,400;1,700&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Work+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <!-- Datetime picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- EventCalendar -->
    <link href="https://cdn.jsdelivr.net/npm/@event-calendar/build@4.3.0/dist/event-calendar.min.css" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="<?= PROTOCOL ?>/assets/style.css">
    <!-- GOOGLE ReCAPTCHA -->
    <?php if (in_array($_SERVER['REQUEST_URI'], $captchaURi)): ?>
    <script type="text/javascript">
      var onloadCallback = function() {
        grecaptcha.render('captcha_element', {
          'sitekey' : '6LfljmMrAAAAAHMI6ykNhcCu8hdvNvommk42IsuX',
          'theme' : 'light',
          'lang' : '<?= $_SESSION['lang'] ?>'
        });
      };
    </script>
    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=<?=$_SESSION['lang']?>"
        async defer>
    </script>
    <?php endif; ?>

    <?php if (in_array($currentPath, $checkPasswordURi) || $isUsersUpdate): ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js" integrity="sha512-TZlMGFY9xKj38t/5m2FzJ+RM/aD5alMHDe26p0mYUMoCF5G7ibfHUQILq0qQPV3wlsnCwL+TPRNK4vIWGLOkUQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <?php endif; ?>
</head>
<body>