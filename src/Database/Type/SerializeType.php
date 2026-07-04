<?php
declare(strict_types=1);

namespace EmailQueue\Database\Type;

use Cake\Database\Driver;
use Cake\Database\Type\BaseType;
use Cake\Database\Type\OptionalConvertInterface;

class SerializeType extends BaseType implements OptionalConvertInterface
{
    /**
     * Creates a PHP value from a stored representation
     *
     * @param mixed $value to unserialize
     * @param \Cake\Database\Driver $driver database driver
     * @return mixed
     */
    public function toPHP(mixed $value, Driver $driver): mixed
    {
        if ($value === null) {
            return null;
        }

        return unserialize($value);
    }

    /**
     * Generates a storable representation of a value
     *
     * @param mixed $value to serialize
     * @param \Cake\Database\Driver $driver database driver
     * @return mixed
     */
    public function toDatabase(mixed $value, Driver $driver): mixed
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return $value->__toString();
        }

        return serialize($value);
    }

    /**
     * Marshal - Return the value as is
     *
     * @param mixed $value php object
     * @return mixed
     */
    public function marshal(mixed $value): mixed
    {
        return $value;
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
