<?php

namespace Php2Core\IO\Xml;

final class Doctype implements IXmlDoctype
{
    /**
     * @var string
     */
    private string $start;

    /**
     * @var string
     */
    private string $end;

    /**
     * @var array
     */
    private array $attributes;

    /**
     * @param string $start
     * @param string $end
     * @param array  $attributes
     */
    public function __construct(string $start, string $end, array $attributes)
    {
        $this -> start = $start;
        $this -> end = $end;
        $this -> attributes = $attributes;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString(): string
    {
        $key = key($this -> attributes);
        if (!is_numeric($key)) {
            $list = array();
            foreach ($this -> attributes as $key => $value) {
                $list[] = $key . '="' . $value . '"';
            }
            return $this -> start . (isset($list[0]) ? ' ' : null) . implode(' ', $list) . ' ' . $this  -> end;
        } else {
            return $this -> start .
                (isset($this -> attributes[0]) ? ' ' : null) .
                implode(' ', $this -> attributes) .
                ' ' .
                $this  -> end;
        }
    }

    /**
     * @return array
     */
    #[\Override]
    public function attributes(): array
    {
        return $this -> attributes;
    }

    /**
     * @param  string  $version
     * @param  string  $encoding
     * @param  boolean $standalone
     * @return Doctype
     */
    public static function xml(string $version = '1.0', string $encoding = 'utf-8', bool $standalone = true): Doctype
    {
        $attr = array();
        $attr['version'] = $version;
        $attr['encoding'] = $encoding;
        $attr['standalone'] = $standalone ? 'yes' : 'no';

        return new Doctype('<?xml', '?>', $attr);
    }

    /**
     * @return Doctype
     */
    public static function html5(): Doctype
    {
        return new Doctype('<!DOCTYPE html', '>', array());
    }

    /**
     * @return Doctype
     */
    public static function htmlStrict(): Doctype
    {
        $attr = array();
        $attr[] = '-//W3C//DTD HTML 4.01//EN';
        $attr[] = 'http://www.w3.org/TR/html4/strict.dtd';

        return new Doctype('<!DOCTYPE HTML PUBLIC', '>', $attr);
    }

    /**
     * @return Doctype
     */
    public static function htmlTransitional(): Doctype
    {
        $attr = array();
        $attr[] = '-//W3C//DTD HTML 4.01 Transitional//EN';
        $attr[] = 'http://www.w3.org/TR/html4/loose.dtd';

        return new Doctype('<!DOCTYPE HTML PUBLIC', '>', $attr);
    }

    /**
     * @return Doctype
     */
    public static function htmlFrameset(): Doctype
    {
        $attr = array();
        $attr[] = '-//W3C//DTD HTML 4.01 Frameset//EN';
        $attr[] = 'http://www.w3.org/TR/html4/frameset.dtd';

        return new Doctype('<!DOCTYPE HTML PUBLIC', '>', $attr);
    }

    /**
     * @return Doctype
     */
    public static function xHtmlStrict(): Doctype
    {
        $attr = array();
        $attr[] = '-//W3C//DTD XHTML 1.0 Strict//EN';
        $attr[] = 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd';

        return new Doctype('<!DOCTYPE html PUBLIC', '>', $attr);
    }

    /**
     * @return Doctype
     */
    public static function xHtmlTransitional(): Doctype
    {
        $attr = array();
        $attr[] = '-//W3C//DTD XHTML 1.0 Transitional//EN';
        $attr[] = 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd';

        return new Doctype('<!DOCTYPE html PUBLIC', '>', $attr);
    }

    /**
     * @return Doctype
     */
    public static function xHtmlFrameset(): Doctype
    {
        $attr = array();
        $attr[] = '-//W3C//DTD XHTML 1.0 Frameset//EN';
        $attr[] = 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd';

        return new Doctype('<!DOCTYPE html PUBLIC', '>', $attr);
    }

    /**
     * @return Doctype
     */
    public static function xHtml(): Doctype
    {
        $attr = array();
        $attr[] = '-//W3C//DTD XHTML 1.1//EN';
        $attr[] = 'http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd';

        return new Doctype('<!DOCTYPE html PUBLIC', '>', $attr);
    }
}
