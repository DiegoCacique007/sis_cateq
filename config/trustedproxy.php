<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proxies de confianza
    |--------------------------------------------------------------------------
    |
    | IP (o lista separada por comas) del proxy inverso o balanceador que está
    | delante del servidor. El middleware TrustProxies de Laravel lee este valor
    | para tomar la IP real del visitante de X-Forwarded-For; los límites por IP
    | del inicio de sesión, el registro y la recuperación de contraseña dependen
    | de ello. Sin valor (por defecto) no se confía en ningún proxy.
    |
    | No uses "*" si el servidor también es accesible directamente desde
    | Internet: cualquiera podría falsificar X-Forwarded-For.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
