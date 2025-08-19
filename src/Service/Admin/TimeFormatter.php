<?php

namespace App\Service\Admin;

/**
 * Clase para manipular y convertir diferentes formatos de tiempo
 */
class TimeFormatter
{
    /**
     * Convierte milisegundos a una cadena con formato de tiempo
     *
     * Casos de uso:
     * - Cuando se tiene la duración en milisegundos
     * - Cuando necesitas mostrar una medición precisa de tiempo
     *
     * @param int $milliseconds Milisegundos a convertir
     * @param string $format Formato deseado: 'hh:mm:ss', 'hh:mm', 'mm:ss', o 'human'
     * @return string Tiempo formateado según el formato especificado
     */
    public function fromMilliseconds(int $milliseconds, string $format = 'hh:mm:ss'): string
    {
        $seconds = intval(($milliseconds / 1000) % 60);
        $minutes = intval(($milliseconds / (1000 * 60)) % 60);
        $hours = intval($milliseconds / (1000 * 60 * 60));

        return $this->buildTimeString($hours, $minutes, $seconds, $format);
    }

    /**
     * Convierte segundos a una cadena con formato de tiempo
     *
     * Casos de uso:
     * - Cuando se tiene la duración en segundos (ej. desde un timestamp)
     * - Cuando trabajas con duración de procesos en segundos
     *
     * @param int|float $seconds Segundos a convertir (acepta decimales)
     * @param string $format Formato deseado: 'hh:mm:ss', 'hh:mm', 'mm:ss', o 'human'
     * @return string Tiempo formateado según el formato especificado
     */
    public function fromSeconds($seconds, string $format = 'hh:mm:ss'): string
    {
        $milliseconds = intval($seconds * 1000);
        return $this->fromMilliseconds($milliseconds, $format);
    }

    /**
     * Convierte una cadena de tiempo a segundos
     *
     * Casos de uso:
     * - Cuando necesitas realizar cálculos con tiempos ingresados por el usuario
     * - Cuando necesitas almacenar tiempos en la base de datos como valores numéricos
     *
     * Formatos soportados:
     * - "hh:mm:ss" (ej. "01:30:45")
     * - "mm:ss" (ej. "05:30")
     * - "hh:mm" (ej. "01:30")
     *
     * @param string $timeString Cadena de tiempo a convertir
     * @return int Tiempo en segundos
     * @throws \InvalidArgumentException Si el formato no es válido
     */
    public function toSeconds(string $timeString): int
    {
        $parts = explode(':', $timeString);
        $count = count($parts);

        if ($count === 3) {
            // Formato hh:mm:ss
            list($hours, $minutes, $seconds) = $parts;
            return ($hours * 3600) + ($minutes * 60) + $seconds;
        } elseif ($count === 2) {
            // Formato mm:ss o hh:mm
            if ($this->looksLikeHoursMinutes($timeString)) {
                // Formato hh:mm
                list($hours, $minutes) = $parts;
                return ($hours * 3600) + ($minutes * 60);
            } else {
                // Formato mm:ss
                list($minutes, $seconds) = $parts;
                return ($minutes * 60) + $seconds;
            }
        } else {
            throw new \InvalidArgumentException("Formato de tiempo no válido: $timeString");
        }
    }

    /**
     * Suma dos cadenas de tiempo
     *
     * Caso de uso:
     * - Cuando necesitas calcular la duración total de múltiples tareas
     * - Cuando necesitas agregar tiempo adicional a una duración existente
     *
     * @param string $time1 Primera cadena de tiempo (hh:mm:ss, mm:ss, hh:mm)
     * @param string $time2 Segunda cadena de tiempo (hh:mm:ss, mm:ss, hh:mm)
     * @param string $format Formato de salida deseado: 'hh:mm:ss', 'hh:mm', 'mm:ss', o 'human'
     * @return string Resultado de la suma en el formato especificado
     */
    public function add(string $time1, string $time2, string $format = 'hh:mm:ss'): string
    {
        $seconds1 = $this->toSeconds($time1);
        $seconds2 = $this->toSeconds($time2);
        $totalSeconds = $seconds1 + $seconds2;

        return $this->fromSeconds($totalSeconds, $format);
    }

    /**
     * Resta dos cadenas de tiempo
     *
     * Caso de uso:
     * - Cuando necesitas calcular la diferencia entre dos tiempos
     * - Cuando necesitas restar tiempo de una duración existente
     *
     * @param string $time1 Tiempo base (hh:mm:ss, mm:ss, hh:mm)
     * @param string $time2 Tiempo a restar (hh:mm:ss, mm:ss, hh:mm)
     * @param string $format Formato de salida deseado: 'hh:mm:ss', 'hh:mm', 'mm:ss', o 'human'
     * @return string Resultado de la resta en el formato especificado
     * @throws \InvalidArgumentException Si el resultado sería negativo
     */
    public function subtract(string $time1, string $time2, string $format = 'hh:mm:ss'): string
    {
        $seconds1 = $this->toSeconds($time1);
        $seconds2 = $this->toSeconds($time2);

        if ($seconds1 < $seconds2) {
            throw new \InvalidArgumentException("La operación resultaría en un tiempo negativo");
        }

        $totalSeconds = $seconds1 - $seconds2;

        return $this->fromSeconds($totalSeconds, $format);
    }

    /**
     * Convierte una cadena de tiempo de un formato a otro
     *
     * Caso de uso:
     * - Cuando necesitas cambiar la representación visual de un tiempo
     * - Cuando necesitas estandarizar formatos de tiempo diferentes
     *
     * @param string $timeString Cadena de tiempo a convertir
     * @param string $format Formato deseado de salida: 'hh:mm:ss', 'hh:mm', 'mm:ss', o 'human'
     * @return string Tiempo en el nuevo formato
     */
    public function changeFormat(string $timeString, string $format = 'hh:mm:ss'): string
    {
        $seconds = $this->toSeconds($timeString);
        return $this->fromSeconds($seconds, $format);
    }

    /**
     * Formatea milisegundos al formato humano legible (ej: 10h 30m 15s)
     *
     * Caso de uso:
     * - Cuando necesitas mostrar duraciones de manera amigable al usuario
     *
     * @param int $milliseconds Milisegundos a formatear
     * @return string Tiempo en formato humano (ej: 10h 30m 15s)
     */
    public function toHuman(int $milliseconds): string
    {
        return $this->fromMilliseconds($milliseconds, 'human');
    }

    /**
     * Determina si una cadena de tiempo con formato de 2 partes parece ser hh:mm o mm:ss
     *
     * @param string $timeString Cadena de tiempo con formato de 2 partes
     * @return bool true si parece ser hh:mm, false si parece ser mm:ss
     */
    protected function looksLikeHoursMinutes(string $timeString): bool
    {
        $parts = explode(':', $timeString);
        if (count($parts) !== 2) {
            return false;
        }

        $first = (int)$parts[0];
        $second = (int)$parts[1];

        // Si el primer número es mayor a 23, probablemente sea mm:ss
        if ($first > 23) {
            return false;
        }

        // Si el primer número es menor a 3, asumimos mm:ss por defecto
        // a menos que el contexto indique lo contrario
        if ($first < 3) {
            return false;
        }

        return true;
    }

    /**
     * Construye una cadena de tiempo según el formato especificado
     *
     * @param int $hours Horas
     * @param int $minutes Minutos
     * @param int $seconds Segundos
     * @param string $format Formato deseado: 'hh:mm:ss', 'hh:mm', 'mm:ss', o 'human'
     * @return string Tiempo formateado
     */
    protected function buildTimeString(int $hours, int $minutes, int $seconds, string $format): string
    {
        $paddedMinutes = str_pad($minutes, 2, '0', STR_PAD_LEFT);
        $paddedSeconds = str_pad($seconds, 2, '0', STR_PAD_LEFT);
        $paddedHours = str_pad($hours, 2, '0', STR_PAD_LEFT);

        switch ($format) {
            case 'hh:mm:ss':
                return "$paddedHours:$paddedMinutes:$paddedSeconds";

            case 'hh:mm':
                return "$paddedHours:$paddedMinutes";

            case 'mm:ss':
                if ($hours > 0) {
                    $totalMinutes = $hours * 60 + $minutes;
                    $paddedMinutes = str_pad($totalMinutes, 2, '0', STR_PAD_LEFT);
                }
                return "$paddedMinutes:$paddedSeconds";

            case 'human':
                $result = '';
                if ($hours > 0) {
                    $result .= $hours . 'h ';
                }
                if ($minutes > 0 || $hours > 0) {
                    $result .= $minutes . 'm ';
                }
                $result .= $seconds . 's';
                return trim($result);

            default:
                return "$paddedHours:$paddedMinutes:$paddedSeconds";
        }
    }
}