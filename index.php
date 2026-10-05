<?php

declare(strict_types=1);

/**
 * Punto de entrada de conveniencia: al abrir la raíz del proyecto
 * (p. ej. http://localhost/aula_virtual/) redirige al front controller público.
 */

header('Location: public/');
exit;
