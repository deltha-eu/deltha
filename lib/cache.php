<?php
/**
 * Caché con Redis para Deltha.
 * Si Redis no está disponible, todo sigue funcionando contra MySQL (sin caché).
 */

function redis_conn(): ?Redis
{
    static $r = null, $intentado = false;
    if ($intentado) {
        return $r;
    }
    $intentado = true;

    if (!class_exists('Redis')) {
        return null; // extensión no instalada (p. ej. en el PC de desarrollo)
    }

    try {
        $r = new Redis();
        $r->connect('127.0.0.1', 6379, 0.2); // 0,2 s de espera máxima
        $r->setOption(Redis::OPT_PREFIX, 'deltha:');
        $r->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
    } catch (Throwable $e) {
        $r = null;
    }
    return $r;
}

/**
 * Devuelve el valor cacheado o lo genera y lo guarda durante $ttl segundos.
 * Nota: no cachea el valor false (se confunde con «no existe»).
 */
function cache_remember(string $clave, int $ttl, callable $generar)
{
    $r = redis_conn();
    if ($r) {
        try {
            $v = $r->get($clave);
            if ($v !== false) {
                return $v;
            }
        } catch (Throwable $e) {
            $r = null;
        }
    }

    $v = $generar();

    if ($r && $v !== false) {
        try {
            $r->setex($clave, $ttl, $v);
        } catch (Throwable $e) {
            // sin caché esta vez
        }
    }
    return $v;
}

/** Borra una o varias claves (al publicar, promocionar, editar...). */
function cache_forget(string ...$claves): void
{
    $r = redis_conn();
    if ($r && $claves) {
        try {
            $r->del($claves);
        } catch (Throwable $e) {
        }
    }
}

/**
 * Límite de peticiones: true si se permite, false si se ha superado.
 * Ej.: rate_limit("voto:$user_id", 30, 60) → 30 votos por minuto.
 * Si Redis no está, permite siempre.
 */
function rate_limit(string $clave, int $max, int $segundos): bool
{
    $r = redis_conn();
    if (!$r) {
        return true;
    }
    try {
        $n = $r->incr("rl:$clave");
        if ($n === 1) {
            $r->expire("rl:$clave", $segundos);
        }
        return $n <= $max;
    } catch (Throwable $e) {
        return true;
    }
}
