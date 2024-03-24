<?php

namespace Studoo\EduFramework\Commands\Extends;

use DateTime;
use DateTimeZone;
use Studoo\EduFramework\Core\ConfigCore;

/**
 * Class CommandBanner
 * Gestion de la banniere de loader
 *
 * @author Benoit Foujols
 */
class CommandBanner
{
    /**
     * @var DateTime $timeExecStart
     */
    private static DateTime $timeExecStart;

    /**
     * @var float|string $timeExecStartMicro
     */
    private static float|string $timeExecStartMicro;

    public static function getDoc(): ?string
    {
        return 'Documentation : https://studoo-app.github.io/edu-framework-doc/';
    }

    /**
     * Banner of the command
     *
     * @return string
     * @throws \Exception
     * @var $message string Add text in banner
     */
    public static function getBanner(): ?string
    {
        $date = new \DateTime("now", new DateTimeZone("Europe/Paris"));
        self::$timeExecStart = $date;
        self::$timeExecStartMicro = microtime(true);

        $banner = "<info>";
        $banner .= "           _          __                           \n";
        $banner .= "   ___  __| |_   _   / _|_ __ __ _ _ __ ___   ___  \n";
        $banner .= "  / _ \/ _` | | | | | |_| '__/ _` | '_ ` _ \ / _ \ \n";
        $banner .= " |  __/ (_| | |_| | |  _| | | (_| | | | | | |  __/ \n";
        $banner .= "  \___|\__,_|\__,_| |_| |_|  \__,_|_| |_| |_|\___| \n";
        $banner .= "                        </info><comment>" . ConfigCore::getConfig('version') . " ";
        $banner .= ConfigCore::getConfig('date_version') . " by studoo collectif</comment>    \n";

        return $banner;
    }

    /**
     * Footer of the command
     *
     * @return String|null
     * @throws \Exception
     */
    public static function getEnd(): ?string
    {
        $banner = "\n<info>+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+</info>\n";
        $banner .= "<comment>Command launched : </comment> \n";
        $banner .= "<comment>Version : " . ConfigCore::getConfig('version') . "</comment> \n";
        $banner .= "<comment>Running time : </comment>" . self::execTime() . "\n";
        $banner .= "<info>+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+</info>\n";

        return $banner;
    }

    /**
     * Calculate Exec Time Command
     *
     * @return String|null
     * @throws \Exception
     */
    private static function execTime(): ?string
    {
        // Calcul Seconde
        $dateEnd = new \DateTime("now", new DateTimeZone('Europe/Paris'));
        $dateDiff = self::$timeExecStart->diff($dateEnd);
        // Calcul MS
        $diffMicro = microtime(true) - self::$timeExecStartMicro;

        if ($diffMicro > 1) {
            $microSec = explode(".", $diffMicro);
            return $dateDiff->format("%H:%I:%S") . "(" . substr($microSec[1], 0, 3) . "ms)";
        }

        return round($diffMicro, 2) . " ms.";
    }
}
