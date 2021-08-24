<?php
declare(strict_types=1);

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Laminas\View\Helper\AbstractHelper;

class SplitText extends AbstractHelper
{
    private ?string $first;
    private ?string $last;


    public function __invoke(string $string = '', int $length = 0): self
    {
        if (strlen($string) < $length) {
            $length = strlen($string);
        }

        $strpos = strpos($string, ' ', $length);

        if ($strpos <= $length) {
            $this->first = $string;
            $this->last = null;
        } else {
            $strpos = strpos($string, ' ', $length);
            $this->first = substr($string, 0, $strpos) ?: null;
            $this->last = substr($string, $strpos, strlen($string) - $strpos) ?: null;
        }

        return $this;
    }

    public function first(): ?string
    {
        return $this->first;
    }

    public function last(): ?string
    {
        return $this->last;
    }
}
