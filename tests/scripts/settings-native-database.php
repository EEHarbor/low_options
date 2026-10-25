<?php

final class SettingsContractDatabase
{
    public $blob;
    public $written;

    /**
     * Retain the native selected field without querying an external database.
     *
     * @param string $field
     * @return self
     */
    public function select($field) { return $this; }

    /**
     * Retain the native settings lookup filter.
     *
     * @param string $field
     * @param mixed $value
     * @return self
     */
    public function where($field, $value) { return $this; }

    /**
     * Return the stored fieldtype row.
     *
     * @param string $table
     * @return self
     */
    public function get($table) { return $this; }

    /**
     * Report the one owned stored row.
     *
     * @return int
     */
    public function num_rows() { return 1; }

    /**
     * Read the original stored bytes without mutation.
     *
     * @param string $field
     * @return mixed
     */
    public function row($field) { return $this->blob; }

    /**
     * Capture the actual Active Record settings publication.
     *
     * @param string $table
     * @param array $data
     * @return bool
     */
    public function update($table, $data) { $this->written = $data['settings']; return true; }
}


