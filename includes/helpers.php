<?php
/**
 * Utility and Sanitization Helpers
 * Implements security output encoding and helper utilities.
 */

declare(strict_types=1);

/**
 * Escapes string for safe HTML rendering (XSS Protection).
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Renders a standard Bootstrap/CSS alert box.
 */
function render_alert(string $message, string $type = 'danger'): string
{
    $validTypes = ['success', 'danger', 'warning', 'info'];
    $alertType = in_array($type, $validTypes, true) ? $type : 'info';

    return sprintf(
        '<div class="alert alert-%s alert-dismissible fade show" role="alert">%s</div>',
        e($alertType),
        e($message)
    );
}

/**
 * Gets an anonymised session identifier hash for prediction log auditing.
 */
function get_anonymised_session_id(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return hash('sha256', session_id() ?: 'anon_session');
}

/**
 * Formats yield numeric value with unit.
 */
function format_yield(float $yield): string
{
    return number_format($yield, 2) . ' kg/ha';
}
