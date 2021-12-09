<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Output;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

class OutputStyler
{
    /**
     * @var string
     */
    private const SUCCESS = 'success';

    /**
     * @var string
     */
    private const WARN = 'warn';

    public static function success(string $string): string
    {
        return '<' . self::SUCCESS . '>' . $string . '</' . self::SUCCESS . '>';
    }

    public static function warn(string $string): string
    {
        return '<' . self::WARN . '>' . $string . '</' . self::WARN . '>';
    }

    public static function init(OutputInterface $output): void
    {
        $warnOutputFormatterStyle = new OutputFormatterStyle('red', null, ['bold']);
        $output->getFormatter()->setStyle(self::WARN, $warnOutputFormatterStyle);

        $successOutputFormatterStyle = new OutputFormatterStyle('green', null, ['bold']);
        $output->getFormatter()->setStyle(self::SUCCESS, $successOutputFormatterStyle);
    }
}
