<?php
declare(strict_types=1);

/**
 * Configuración de PayPal (REST API v2).
 *
 * Deja las constantes vacías y define las credenciales como variables de
 * entorno para no subirlas al repositorio:
 *
 *   PAYPAL_CLIENT_ID
 *   PAYPAL_CLIENT_SECRET
 *
 * Con PAYPAL_SANDBOX = true se usa el entorno de pruebas de PayPal.
 */

const PAYPAL_CLIENT_ID     = '';
const PAYPAL_CLIENT_SECRET = '';
const PAYPAL_SANDBOX       = true;
const PAYPAL_MONEDA        = 'MXN';

/**
 * Habilita el botón "Simular pago aprobado".
 *
 * Cámbialo a false en producción para aceptar únicamente pagos reales.
 * También se puede controlar con la variable de entorno PAYPAL_DEMO=0.
 */
const PAYPAL_DEMO_HABILITADO = true;

function paypal_demo_habilitado(): bool
{
    $valor = getenv('PAYPAL_DEMO');

    if ($valor !== false && $valor !== '') {
        return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
    }

    return PAYPAL_DEMO_HABILITADO;
}

function paypal_client_id(): string
{
    $valor = getenv('PAYPAL_CLIENT_ID');

    return ($valor !== false && $valor !== '') ? $valor : PAYPAL_CLIENT_ID;
}

function paypal_client_secret(): string
{
    $valor = getenv('PAYPAL_CLIENT_SECRET');

    return ($valor !== false && $valor !== '') ? $valor : PAYPAL_CLIENT_SECRET;
}

/**
 * Indica si hay credenciales configuradas.
 */
function paypal_configurado(): bool
{
    return paypal_client_id() !== '' && paypal_client_secret() !== '';
}

/**
 * URL base de la API según el entorno.
 */
function paypal_base_url(): string
{
    return PAYPAL_SANDBOX
        ? 'https://api-m.sandbox.paypal.com'
        : 'https://api-m.paypal.com';
}
