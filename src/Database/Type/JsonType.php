<?php
declare(strict_types=1);

namespace EmailQueue\Database\Type;

use Cake\Database\Driver;
use Cake\Database\Type\BaseType;
use Cake\Database\Type\OptionalConvertInterface;

class JsonType extends BaseType implements OptionalConvertInterface
{
    /**
     * Decodes a JSON string
     *
     * @param mixed $value json string to decode
     * @param \Cake\Database\Driver $driver database driver
     * @return mixed
     */
    public function toPHP(mixed $value, Driver $driver): mixed
    {
        if ($value === null) {
            return null;
        }

        return json_decode($value, true);
    }

    /**
     * Marshal - Decodes a JSON string
     *
     * @param mixed $value json string to decode
     * @return mixed
     */
    public function marshal(mixed $value): mixed
    {
        if (is_array($value) || $value === null) {
            return $value;
        }

        return json_decode($value, true);
    }

    /**
     * Returns the JSON representation of a value
     *
     * @param mixed $value string or object to encode
     * @param \Cake\Database\Driver $driver database driver
     * @return mixed
     */
    public function toDatabase(mixed $value, Driver $driver): mixed
    {
        return json_encode($value);
    }

    /**
     * Returns whether the cast to PHP is required to be invoked
     *
     * @return bool always true
     */
    public function requiresToPhpCast(): bool
    {
        return true;
    }
}
