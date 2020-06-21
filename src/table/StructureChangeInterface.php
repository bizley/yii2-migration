<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface StructureChangeInterface
{
    /**
     * Returns table name of the change.
     * @return string
     */
    public function getTable(): string;

    /**
     * Returns method of the change.
     * @return string
     */
    public function getMethod(): string;

    /**
     * Returns value of the change based on the method.
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return mixed Change value
     */
    public function getValue(string $schema = null, string $engineVersion = null);
}
