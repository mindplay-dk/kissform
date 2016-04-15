<?php
// This is global bootstrap for autoloading

use mindplay\lang;

lang::$on_error = function ($message) {
    throw new RuntimeException($message);
};
