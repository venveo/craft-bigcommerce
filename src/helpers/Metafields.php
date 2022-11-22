<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\helpers;

use BigCommerce\ApiV3\ResourceModels\Metafield;
use craft\helpers\Json;

// TODO: This file needs to be updated for BigCommerce
class Metafields
{
    /**
     * @var array Data types that should be expanded into an array.
     * @see https://bigcommerce.dev/apps/metafields/types
     */
    public const JSON_TYPES = [
        'dimension',
        'json',
        'money',
        'rating',
        'volume',
    ];


    /**
     * Unpacks metadata from the BigCommerce API.
     *
     * @param Metafield[] $fields
     * @return array
     */
    public static function unpack(array $fields): array
    {
        $data = [];

        foreach ($fields as $field) {
            $data[$field->key] = static::decode($field);
        }

        return $data;
    }

    /**
     * Turn a metafield API resource into a simple value, based on its type.
     */
    public static function decode(Metafield $field)
    {
        $value = $field->value;

        if (in_array($field->type, static::JSON_TYPES) || strpos($field->type, static::LIST_PREFIX) === 0) {
            $value = Json::decodeIfJson($value);
        }

        return $value;
    }
}
