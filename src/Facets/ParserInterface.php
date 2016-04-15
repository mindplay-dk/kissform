<?php

namespace mindplay\kissform\Facets;

/**
 * This interface may be implemented by some Field types, if they provide an input parser method.
 *
 * Note that input parser methods must be error-tolerant, and must return NULL for invalid input.
 */
interface ParserInterface
{
    /**
     * Attempts to parse the given input; returns NULL on failure.
     *
     * @param string $input
     *
     * @return mixed|null
     */
    public function parseInput($input);
}
