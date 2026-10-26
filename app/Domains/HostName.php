<?php

namespace App\Domains;

/** Normalises and checks a host an organiser types ("https://Register.MyRace.in/" -> register.myrace.in). */
final class HostName
{
    public static function normalise(string $input): string
    {
        $host = strtolower(trim($input));
        $host = (string) preg_replace('#^[a-z]+://#', '', $host);
        $host = explode('/', $host)[0];
        $host = explode(':', $host)[0];

        return rtrim($host, '.');
    }

    /** Null when fine, else why the host cannot be used. */
    public static function problem(string $host): ?string
    {
        if (! preg_match('/^(?=.{4,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $host)) {
            return __('Enter a domain like register.yourrace.in (no https:// or paths).');
        }
        foreach ((array) config('domains.app_hosts') as $app) {
            if ($host === $app || str_ends_with($host, '.'.$app)) {
                return __('That is an EventFlow address. Use a domain you own.');
            }
        }

        return null;
    }

    public static function isAppHost(string $host): bool
    {
        return in_array(strtolower($host), (array) config('domains.app_hosts'), true);
    }
}
