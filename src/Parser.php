<?php
namespace Wasinger\Oeffnungszeitenparser;

/**
 * Parser for strings with typical German opening hours
 *
 */
class Parser
{
    const EXPECT_DAY = 1;
    const EXPECT_TIME_BEGIN_OR_ANOTHER_DAY = 2;
    const EXPECT_DAY_RANGE_END = 3;
    const EXPECT_TIME_END = 4;
    const EXPECT_ANOTHER_TIME_RANGE = 5;
    const TIME_RANGE_COMPLETE = 6;

    private $input;
    private $result = [];
    private $letterstack = '';
    private $numberstack = '';

    private $state = self::EXPECT_DAY;

    private $open_days = [];
    private $last_day;
    private $time_begin;

    private $day_identifiers = [
        'sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'
    ];

    protected $local_days = [
        'so', 'mo', 'di', 'mi', 'do', 'fr', 'sa'
    ];

    protected $addition_symbol_words = ['u', 'und'];

    protected $range_symbol_words = ['bis'];

    /**
     *
     * @param string $input String to parse in UTF-8 encoding
     */
    public function __construct(string $input)
    {
        $this->input = $input;
    }

    /**
     * @return array Associative array of arrays:
     * [
     *   'mon' => [
     *      0 => [
     *          'begin' => '7:00'
     *          'end' => '13:00'
     *      ],
     *      1 => [
     *          'begin' => '14:00',
     *          'end' => '18:00'
     *      ],
     *   'tue' => [
     *          ...
     *   ],
     *   ...
     * ]
     *
     *
     */
    public function parse()
    {
        $len = mb_strlen($this->input, "UTF-8");
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($this->input, $i, 1, "UTF-8");
            if (preg_match('/\p{L}/u', $char)) {
                $this->consumeLetter($char);
            } else if (preg_match('/\p{N}/u', $char)) {
                $this->consumeNumber($char);
            } else if ($char == ':') {
                $this->consumeColon();
            } else if ($char == '.') {
                $this->consumeDot();
            } else if ($char == '-') {
                $this->consumeMinus();
            } else if ($char == '+') {
                $this->consumePlus();
            } else {
                $this->consumeOther($char);
            }
        }
        $this->finalizeToken();

        // sort result by day of week and filter out empty days
        return array_filter(array_merge(array_combine($this->day_identifiers, array_fill(0, 7, null)), $this->result));
    }

    public static function createAndParse($string)
    {
        $p = new static($string);
        return $p->parse();
    }

    private function consumeLetter($l)
    {
        $this->letterstack .= $l;
    }
    private function consumeNumber($n)
    {
        $this->numberstack .= $n;
    }

    /**
     * colon may be used as separator in times (hh:mm) or as punctuation that can be ignored
     */
    private function consumeColon()
    {
        if (!empty($this->numberstack)) {
            $this->numberstack .= ':';
        } else {
            $this->consumeOther(':');
        }
    }

    /**
     * dot may be used as separator in times (hh.mm) or as punctuation that can be ignored
     */
    private function consumeDot()
    {
        if (!empty($this->numberstack)) {
            $this->numberstack .= ':';
        } else {
            $this->consumeOther('.');
        }
    }

    private function consumeMinus()
    {
        $this->finalizeToken();
        $this->processRangeSymbol();
    }

    private function consumePlus()
    {
        $this->finalizeToken();
        $this->processAdditionSymbol();
    }

    private function processAdditionSymbol()
    {
        if ($this->state == self::EXPECT_TIME_BEGIN_OR_ANOTHER_DAY) {
            $this->state = self::EXPECT_DAY;
        } elseif ($this->state == self::TIME_RANGE_COMPLETE) {
            $this->state = self::EXPECT_ANOTHER_TIME_RANGE;
        }
    }

    private function processRangeSymbol()
    {
        if ($this->state == self::EXPECT_TIME_BEGIN_OR_ANOTHER_DAY) {
            $this->state = self::EXPECT_DAY_RANGE_END;
        }
    }

    private function consumeOther($c)
    {
        switch ($c) {
            default:
                $this->finalizeToken();
        }
    }

    private function finalizeToken()
    {
        if (!empty($this->letterstack)) {
            $this->finalizeWordToken();
        }
        if (!empty($this->numberstack)) {
            $this->finalizeTimeToken();
        }
    }

    private function finalizeWordToken()
    {
        $word = \mb_strtolower($this->letterstack);

        if (in_array($word, $this->addition_symbol_words)) {
            $this->processAdditionSymbol();
        } else if (in_array($word, $this->range_symbol_words)){
            $this->processRangeSymbol();
        } else if (in_array(substr($word, 0, 2), $this->local_days)) {
            $key = \array_search(substr($word, 0, 2), $this->local_days);
            $this->processDay($this->day_identifiers[$key]);
        }
        $this->letterstack = '';
    }

    private function processDay($day)
    {
        if ($this->state == self::EXPECT_DAY_RANGE_END) {
            $pos_start = \array_search($this->last_day, $this->day_identifiers);
            $pos_end = \array_search($day, $this->day_identifiers);
            $range = array_slice($this->day_identifiers, $pos_start, $pos_end - $pos_start + 1);
            foreach ($range as $day) {
                $this->open_days[$day] = true;
            }
            $this->last_day = '';
        } else {
            if ($this->state == self::TIME_RANGE_COMPLETE) {
                $this->open_days = [];
            }
            $this->open_days[$day] = true;
            $this->last_day = $day;
        }
        $this->state = self::EXPECT_TIME_BEGIN_OR_ANOTHER_DAY;
    }

    private function finalizeTimeToken()
    {
        $this->processTime($this->numberstack);
        $this->numberstack = '';
    }

    private function processTime($time)
    {
        if ($this->state == self::EXPECT_TIME_END) {
            $time_start = $this->time_begin;
            $time_end = $time;
            foreach ($this->open_days as $day => $open) {
                if ($open) {
                    $this->result[$day][] = [
                        'begin' => $this->formatTimeString($time_start),
                        'end' => $this->formatTimeString($time_end)
                    ];
                }
            }
            $this->time_begin = '';
            $this->state = self::TIME_RANGE_COMPLETE;
        } elseif (
            $this->state == self::EXPECT_TIME_BEGIN_OR_ANOTHER_DAY
            || $this->state == self::EXPECT_ANOTHER_TIME_RANGE
        ) {
            $this->time_begin = $time;
            $this->state = self::EXPECT_TIME_END;
        }
    }

    private function formatTimeString($s)
    {
        if (strpos($s, ':') > 0) {
            list($h, $m) = @explode(':', $s);
        } else {
            $h = $s;
            $m = 0;
        }
        return sprintf("%'.02d:%'.02d", (int) $h, (int) $m);
    }
}