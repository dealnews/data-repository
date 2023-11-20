<?php

namespace DealNews\Repository;

/**
 * Repository for loading data only once during a request/process
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     Repository
 *
 * @phan-suppress PhanUnreferencedClass
 */
class Repository {

    /**
     * A constant for read callback types.
     */
    public const HANDLE_READ = 'read';

    /**
     * A constant for write callback types.
     */
    public const HANDLE_WRITE = 'write';

    /**
     * Stores the data which has been loaded
     * @var array
     */
    protected array $storage = [];

    /**
     * List of handlers for loading data
     * @var array
     */
    protected array $read_handlers = [];

    /**
     * List of handlers for writing data
     * @var array
     */
    protected array $write_handlers = [];

    /**
     * Loads a single object
     *
     * @param  string     $type        Name of a registered handler
     * @param  int|string $identifier  Identifier of object to get
     *
     * @return null|mixed
     */
    public function getOne(string $type, int|string $identifier): mixed {
        $object = null;
        $result = $this->get($type, [$identifier]);
        if (!empty($result[$identifier])) {
            $object = $result[$identifier];
        }

        return $object;
    }

    /**
     * Loads/returns data. The return value is an array that is keyed
     * by the identifiers. If data for an identifier is not found, it
     * will not be returned as a key. The return data is returned in the
     * same order as the array of identifiers
     *
     * @param  string $type        Name of a registered handler
     * @param  array  $identifiers Array of identifiers
     *
     * @return array
     */
    public function get(string $type, array $identifiers): array {

        $values = [];

        // Determine which values are already loaded and which
        // will need to be loaded
        $fetch = [];
        foreach ($identifiers as $id) {
            if (!isset($this->storage[$type][$id])) {
                $fetch[] = $id;
            }
        }

        // load any missing data
        if (!empty($fetch)) {
            if (!isset($this->read_handlers[$type])) {
                throw new \LogicException("There is no repository read handler for `$type`");
            }
            $data = $this->read_handlers[$type]($identifiers);
            if (!empty($data) && is_array($data)) {
                $this->setMulti($type, $data);
            }
        }

        // fill the return array preserving the order of the identifiers
        foreach ($identifiers as $id) {
            if (isset($this->storage[$type][$id])) {
                $values[$id] = $this->storage[$type][$id];
            }
        }

        return $values;
    }

    /**
     * Stores data in the repository
     * @param  string      $type        Name of a registered handler
     * @param  int|string  $identifier  And identifier for the object
     * @param  mixed       $value       The value to store
     * @return bool
     */
    public function set(string $type, int|string $identifier, mixed $value): bool {
        if (!isset($this->storage[$type])) {
            $this->storage[$type] = [];
        }
        $this->storage[$type][$identifier] = $value;

        return true;
    }

    /**
     * Stores an array of data in the repository
     * @param  string $type Name of a registered handler
     * @param  array  $data Array of data where the keys are the identifiers
     *                      and the values are the values to store.
     * @return bool
     */
    public function setMulti(string $type, array $data): bool {
        $success = true;
        foreach ($data as $id => $value) {
            $success = $this->set($type, $id, $value);
            if (!$success) {
                break;
            }
        }

        return $success;
    }

    /**
     * Stores data in the repository and calls the write handler to persist
     * the data.
     * @param  string $type        Name of a registered handler
     * @param  mixed  $value       The value to store
     * @return bool|mixed
     */
    public function save(string $type, mixed $value): mixed {
        if (!isset($this->write_handlers[$type])) {
            throw new \LogicException("There is no repository write handler for `$type`");
        }
        $data  = $this->write_handlers[$type]($value);
        $value = false;
        if (is_array($data)) {
            $identifier = key($data);
            $value      = current($data);
            if (!$this->set($type, $identifier, $value)) {
                $value = false;
            }
        }

        return $value;
    }

    /**
     * Registers a callback for loading data for the set type.
     *
     * @param  string    $type           Name of a registered handler
     * @param  callable  $read_callback  Callback for loading data. The callback
     *                                   must accept an array of identifiers for its
     *                                   first paramater and must only require
     *                                   one parameter.
     * @param  ?callable $write_callback Callback for writing data. The callback
     *                                   must accept a single value to store
     *                                   in the first parameter. It must return
     *                                   boolean false if the data could not be
     *                                   saved. If the data is saved successfully
     *                                   it must return an array with one item
     *                                   where the key is the identifier and
     *                                   the value is the data.
     * @return void
     */
    public function register(string $type, callable $read_callback, ?callable $write_callback = null): void {
        $this->read_handlers[$type] = $read_callback;
        if (!empty($write_callback)) {
            $this->write_handlers[$type] = $write_callback;
        }
    }

    /**
     * Returns whether this repository responds for a certain type.
     *
     * @param string $type          A type as used in register()
     * @param string $callback_type A callback type (one of HANDLE_READ or
     *                              HANDLE_WRITE)
     * @return bool
     */
    public function respondsForType(string $type, string $callback_type = 'read'): bool {
        if ($callback_type == self::HANDLE_READ && array_key_exists($type, $this->read_handlers)) {
            return true;
        } elseif ($callback_type == self::HANDLE_WRITE && array_key_exists($type, $this->write_handlers)) {
            return true;
        }

        return false;
    }
}
