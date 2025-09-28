<?php

if (!function_exists('adminRoute')) {
    /**
     * Fungsi helper untuk mengecek dan membuat URL route dengan prefix admin
     * 
     * @param string $name Nama route tanpa prefix admin
     * @param array $params Parameter route (opsional)
     * @param bool $absolute Apakah URL absolute
     * @return string URL route atau # jika route tidak ditemukan
     */
    function adminRoute(string $name, array $params = [], bool $absolute = true): string
    {
        $prefixed = str_starts_with($name, 'admin.') ? $name : "admin.$name";
        return route($prefixed, $params, $absolute);
    }
}