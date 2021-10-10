<?php

declare(strict_types=1);

chdir(dirname(__DIR__));

require_once 'vendor/autoload.php';

$container = require 'config/container.php';
$env = $container->get('env');

if (!defined('SERVER_URL')) {
    switch ($env) {
        case 'development':
            $serverUrl = 'http://192.168.50.106:9090';
            break;

        case 'local':
        default:
            $serverUrl = 'http://192.168.50.106:8090';
    }

    define('SERVER_URL', $serverUrl);
}

/**
 * @OA\Info(
 *   title="Suppliers",
 *   version="0.0.1",
 * )
 */

/**
 * @OA\Server(
 *   url=SERVER_URL,
 * )
 */

//////////////////////////// Tags

/**
 * @OA\Tag(
 *   name="Поставщики",
 * )
 */

/**
 * @OA\Tag(
 *   name="График работы поставщиков",
 * )
 */

/**
 * @OA\Tag(
 *   name="Предложения поставщиков",
 * )
 */

/**
 * @OA\Tag(
 *   name="График доставок предложений",
 * )
 */

/**
 * @OA\Tag(
 *   name="Секторы поставщиков",
 * )
 */

/**
 * @OA\Tag(
 *   name="Интервалы доставок секторов",
 * )
 */

/**
 * @OA\Tag(
 *   name="Доставка заказа",
 * )
 */

//////////////////////////// Parameters

/**
 * @OA\Parameter(
 *   name="page",
 *   in="query",
 *   @OA\Schema(
 *     type="integer",
 *     minimum=1,
 *     default=1,
 *   ),
 * )
 */

/**
 * @OA\Parameter(
 *   name="limit",
 *   in="query",
 *   @OA\Schema(
 *     type="integer",
 *     minimum=1,
 *     default=50,
 *   ),
 * )
 */

//////////////////////////// Schemas

/**
 * @OA\Schema(
 *   schema="ResponseStatusOk",
 *   type="string",
 *   pattern="^ok$",
 *   example="ok",
 * )
 */

/**
 * @OA\Schema(
 *   schema="ResponseOk",
 *   type="object",
 *   @OA\Property(
 *     property="status",
 *     ref="#/components/schemas/ResponseStatusOk"
 *   ),
 *   @OA\Property(
 *     property="data",
 *     type="array",
 *     @OA\Items(),
 *   )
 * )
 */

/**
 * @OA\Schema(
 *   schema="ResponseStatusViolations",
 *   type="string",
 *   pattern="^violations$",
 *   example="violations",
 * )
 */

/**
 * @OA\Schema(
 *   schema="ResponseViolations",
 *   type="object",
 *   @OA\Property(
 *     property="status",
 *     ref="#/components/schemas/ResponseStatusViolations"
 *   ),
 *   @OA\Property(
 *     property="violations",
 *     type="array",
 *     @OA\Items(
 *       type="object",
 *       @OA\Property(
 *         property="message",
 *         type="string",
 *       ),
 *       @OA\Property(
 *         property="property",
 *         type="string",
 *       ),
 *     ),
 *   ),
 *   @OA\Property(
 *     property="data",
 *     type="array",
 *     @OA\Items(),
 *   )
 * )
 */

/**
 * @OA\Schema(
 *   schema="ResponseStatusError",
 *   type="string",
 *   pattern="^error$",
 *   example="error",
 * )
 */

/**
 * @OA\Schema(
 *   schema="ResponseError",
 *   type="object",
 *   @OA\Property(
 *     property="status",
 *     ref="#/components/schemas/ResponseStatusError"
 *   ),
 *   @OA\Property(
 *     property="message",
 *     type="string",
 *   ),
 *   @OA\Property(
 *     property="data",
 *     type="array",
 *     @OA\Items(),
 *   )
 * )
 */
