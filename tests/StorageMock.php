<?php

namespace DealNews\Repository\Tests;

class StorageMock {
    public $data = [];

    public function load($ids) {
        $values = [];
        foreach ($ids as $id) {
            if (isset($this->data[$id])) {
                $values[$id] = $this->data[$id];
            }
        }

        return $values;
    }

    public function save(array $value) {
        if (!isset($value['id'])) {
            $value['id'] = uniqid();
        }
        $this->data[$value['id']] = $value;

        return [$value['id'] => $this->data[$value['id']]];
    }
}
