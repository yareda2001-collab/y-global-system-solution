<?php
$envFile = dirname(__DIR__) . '/.env';

if (!is_file($envFile)) {
    return;
}

if (!is_readable($envFile)) {
    throw new RuntimeException('The local environment file is not readable.');
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES);
if ($lines === false) {
    throw new RuntimeException('The local environment file could not be read.');
}

foreach ($lines as $lineNumber => $line) {
    $line = trim($line);
    if ($line === '' || strpos($line, '#') === 0) {
        continue;
    }

    if (!preg_match('/^([A-Z][A-Z0-9_]*)=(.*)$/', $line, $matches)) {
        throw new RuntimeException('Invalid environment entry on line ' . ($lineNumber + 1) . '.');
    }

    $name = $matches[1];
    $value = trim($matches[2]);
    $lastCharacter = substr($value, -1);
    $quoted = strlen($value) >= 2
        && (($value[0] === '"' && $lastCharacter === '"') || ($value[0] === "'" && $lastCharacter === "'"));

    if (($value !== '' && ($value[0] === '"' || $value[0] === "'")) && !$quoted) {
        throw new RuntimeException('Unclosed quote in environment entry on line ' . ($lineNumber + 1) . '.');
    }

    if ($quoted) {
        $quote = $value[0];
        $value = substr($value, 1, -1);
        $value = str_replace(['\\\\', '\\' . $quote], ['\\', $quote], $value);
    }

    if (getenv($name) === false) {
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
