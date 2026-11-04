<?php
declare(strict_types=1);

/**
 * Cliente mínimo de la API REST de PayPal (Orders v2).
 *
 * Nunca recibe montos ni datos desde el navegador: el total se calcula
 * siempre en el servidor a partir del carrito.
 */

require_once __DIR__ . '/../config/paypal.php';

/**
 * Ejecuta una petición JSON contra la API de PayPal.
 *
 * @param array<string, mixed>|object|null $cuerpo
 * @param array<int, string>               $cabeceras
 *
 * @return array{codigo: int, datos: array<string, mixed>|null, error: string|null}
 */
function paypal_http(
    string $metodo,
    string $ruta,
    array|object|null $cuerpo = null,
    ?string $token = null,
    array $cabeceras = []
): array {
    $ch = curl_init(paypal_base_url() . $ruta);

    $headers = ['Content-Type: application/json', 'Accept: application/json'];

    if ($token !== null) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    foreach ($cabeceras as $cabecera) {
        $headers[] = $cabecera;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $metodo,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    if ($cuerpo !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($cuerpo, JSON_UNESCAPED_UNICODE));
    }

    $respuesta = curl_exec($ch);
    $codigo    = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $errorCurl = curl_error($ch);
    curl_close($ch);

    if ($respuesta === false) {
        return [
            'codigo' => 0,
            'datos'  => null,
            'error'  => 'Error de conexión con PayPal: ' . $errorCurl,
        ];
    }

    $datos = json_decode((string) $respuesta, true);

    return [
        'codigo' => $codigo,
        'datos'  => is_array($datos) ? $datos : null,
        'error'  => null,
    ];
}

/**
 * Obtiene un token de acceso con las credenciales configuradas.
 *
 * @return array{ok: bool, token: string|null, error: string|null}
 */
function paypal_token(): array
{
    if (!paypal_configurado()) {
        return ['ok' => false, 'token' => null, 'error' => 'PayPal no está configurado.'];
    }

    $ch = curl_init(paypal_base_url() . '/v1/oauth2/token');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_USERPWD        => paypal_client_id() . ':' . paypal_client_secret(),
        CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $respuesta = curl_exec($ch);
    $codigo    = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $errorCurl = curl_error($ch);
    curl_close($ch);

    if ($respuesta === false) {
        return ['ok' => false, 'token' => null, 'error' => 'Error de conexión: ' . $errorCurl];
    }

    $datos = json_decode((string) $respuesta, true);

    if ($codigo !== 200 || !is_array($datos) || empty($datos['access_token'])) {
        error_log('PayPal token: ' . $respuesta);

        return ['ok' => false, 'token' => null, 'error' => 'No fue posible autenticar con PayPal.'];
    }

    return ['ok' => true, 'token' => (string) $datos['access_token'], 'error' => null];
}

/**
 * Crea una orden de PayPal por el total indicado.
 *
 * @return array{ok: bool, id: string|null, error: string|null}
 */
function paypal_crear_orden(float $total, string $referencia): array
{
    $token = paypal_token();

    if (!$token['ok']) {
        return ['ok' => false, 'id' => null, 'error' => $token['error']];
    }

    $cuerpo = [
        'intent'         => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => $referencia,
            'description'  => 'Congreso SysIoT 2026',
            'amount'       => [
                'currency_code' => PAYPAL_MONEDA,
                'value'         => number_format($total, 2, '.', ''),
            ],
        ]],
        'application_context' => [
            'brand_name' => 'Congreso SysIoT 2026',
            'user_action' => 'PAY_NOW',
        ],
    ];

    $respuesta = paypal_http(
        'POST',
        '/v2/checkout/orders',
        $cuerpo,
        $token['token'],
        ['PayPal-Request-Id: ' . $referencia]
    );

    if ($respuesta['error'] !== null) {
        return ['ok' => false, 'id' => null, 'error' => $respuesta['error']];
    }

    $id = $respuesta['datos']['id'] ?? null;

    if (($respuesta['codigo'] !== 201 && $respuesta['codigo'] !== 200) || !is_string($id)) {
        error_log('PayPal crear orden: ' . json_encode($respuesta['datos']));

        return ['ok' => false, 'id' => null, 'error' => 'PayPal no pudo crear la orden.'];
    }

    return ['ok' => true, 'id' => $id, 'error' => null];
}

/**
 * Captura una orden previamente aprobada por el usuario.
 *
 * @return array{
 *   ok: bool,
 *   estado: string|null,
 *   captura_id: string|null,
 *   monto: string|null,
 *   moneda: string|null,
 *   error: string|null
 * }
 */
function paypal_capturar_orden(string $orden_id): array
{
    $token = paypal_token();

    if (!$token['ok']) {
        return [
            'ok' => false, 'estado' => null, 'captura_id' => null,
            'monto' => null, 'moneda' => null, 'error' => $token['error'],
        ];
    }

    $respuesta = paypal_http(
        'POST',
        '/v2/checkout/orders/' . rawurlencode($orden_id) . '/capture',
        (object) [],
        $token['token'],
        ['PayPal-Request-Id: captura-' . $orden_id]
    );

    if ($respuesta['error'] !== null) {
        return [
            'ok' => false, 'estado' => null, 'captura_id' => null,
            'monto' => null, 'moneda' => null, 'error' => $respuesta['error'],
        ];
    }

    $datos   = $respuesta['datos'] ?? [];
    $captura = $datos['purchase_units'][0]['payments']['captures'][0] ?? [];

    if ($respuesta['codigo'] !== 201 && $respuesta['codigo'] !== 200) {
        error_log('PayPal capturar orden: ' . json_encode($datos));

        return [
            'ok' => false, 'estado' => null, 'captura_id' => null,
            'monto' => null, 'moneda' => null, 'error' => 'PayPal no pudo capturar la orden.',
        ];
    }

    return [
        'ok'         => true,
        'estado'     => isset($datos['status']) ? (string) $datos['status'] : null,
        'captura_id' => isset($captura['id']) ? (string) $captura['id'] : null,
        'monto'      => isset($captura['amount']['value']) ? (string) $captura['amount']['value'] : null,
        'moneda'     => isset($captura['amount']['currency_code']) ? (string) $captura['amount']['currency_code'] : null,
        'error'      => null,
    ];
}
