<?php

require __DIR__ . '/../vendor/autoload.php';

/**
 * Function for helping debug tests since modern PHP Unit
 * does not allow var_dump to send output to STDOUT.
 */
function _debug() {
    fwrite(STDERR, "\nSTART DEBUG\n");
    fwrite(STDERR, "###########\n");
    $args = func_get_args();
    foreach ($args as $arg) {
        fwrite(STDERR, trim(var_export($arg, true)) . "\n");
    }
    fwrite(STDERR, "###########\n");
    fwrite(STDERR, "END DEBUG\n\n");
}
