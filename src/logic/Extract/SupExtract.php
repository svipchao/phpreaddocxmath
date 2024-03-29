<?php

namespace cccms\phpreaddocxmath\logic\Extract;

use cccms\phpreaddocxmath\logic\ExtractAbstruct;

/**
 * Class PopExtract 上标转换
 * @package PHPReadDocx\src\logic\Extract
 */
class SupExtract extends ExtractAbstruct
{
    /**
     * <w:vertAlign w:val="superscript"/></w:rPr><w:t>
     * </w:t>
     * @var string
     */
    protected $grep = '/<w:vertAlign w:val="superscript".*?w:r>/';

    /**
     * @var string
     */
    protected $pre_index = '[suptag]';
    /**
     * @var string
     */
    protected $end_index = '[/suptag]';

    /**
     * @var string
     */
    protected $pre_tag = '<sup>';

    /**
     * @var string
     */
    protected $end_tag = '</sup>';

    /**
     * @return mixed
     */
    public function handel()
    {
        if (!$this->grep) {
            return $this->xml;
        }
        preg_match_all($this->grep, $this->xml, $arr);
        foreach ($arr[0] as $v) {
            $text = trim(strip_tags($v));
            $string_after = str_replace('<w:t>' . $text . '</w:t>', $this->pre_index . $text . $this->end_index, $v);
            if ($text) {
                $this->xml = str_replace($v, $string_after, $this->xml);
            }
        }
        return $this->xml;
    }
}
