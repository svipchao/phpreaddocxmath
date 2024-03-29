<?php

namespace cccms\phpreaddocxmath;

use cccms\phpreaddocxmath\ImageDiyHandelInterface;
use cccms\phpreaddocxmath\logic\{DocxService, ExtractAbstruct};
use cccms\phpreaddocxmath\logic\Extract\{ImgExtract, MathExtract};

/**
 * 读取dock服务
 * Class ReadDocxService
 * @package ReadDocxService
 */
class ReadDocxService
{
    /**
     * @var
     */
    private $file_url;

    /**
     * @var DocxService;
     */
    private $docxService;

    /**
     * @var array
     */
    private $docx_arr = [];

    /**
     * @var
     */
    private $img_handel_class;

    /**
     * ReadDocxService constructor.
     */
    public function __construct($tmp_patch = '/tmp')
    {
        $this->docxService = new DocxService($tmp_patch);
    }

    /**
     * @param $file_url
     * @return $this
     * @throws \Exception
     */
    public function setFileUrl($file_url)
    {
        $this->file_url = trim($file_url);
        if (!@fopen($this->file_url, 'r')) {
            throw new \Exception('file is not exists:' . $file_url);
        }
        $this->docxService->readFile($file_url);
        return $this;
    }

    /**
     * 转换文档成html数据
     * @param string $filename 文件名称，如果为空，则不创建文件
     * @return array
     * @throws \Exception
     */
    public function extractToHtml($filename = '')
    {
        if (!$this->file_url) {
            throw new \Exception('file_url is required');
        }
        $this->docxService->load();
        foreach ($this->docxService->docx_data_arr as $xml) {
            $this->docx_arr[] = '<div>' . $this->getHtmlString($xml) . '</div>';
        }
        // 临时文件夹不存在 会报错 诗无尽头
        // $this->docxService->delTempFile();
        if ($filename) {
            $docx = '';
            foreach ($this->docx_arr as $docx_string) {
                $docx .= $docx_string;
            }
            $filename = str_replace('.html', '', $filename);
            $html = '
                        <html>
                        <head>
                            <title></title>
                            <meta http-equiv="content-type" content="text/html;charset=utf-8">
                            <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
                        </head>
                        <body>
                            ' . $docx . '
                        </body>
                        </html>
                    ';
            $filename = $filename . '.html';
            $myfile = fopen($filename, 'w');
            fwrite($myfile, $html);
            fclose($myfile);
            return $this->docx_arr;
        }
        return $this->docx_arr;
    }

    /**
     * @param $xml
     * @return mixed
     */
    private function getHtmlString($xml)
    {
        $math = '';
        // 循环识别xml类型
        $xmlStr = $xml;
        if (str_contains($xmlStr, 'm:oMath')) {
            // 公式
            $class = new \cccms\phpreaddocxmath\logic\Extract\MathExtract($xml, $this->docxService);
            $math = $class;
        }
        if (str_contains($xmlStr, 'a:blip')) {
            // 图片
            $class = new \cccms\phpreaddocxmath\logic\Extract\ImgExtract($xml, $this->docxService);
            $xml = $class->handel($this->img_handel_class);
        }
        if (str_contains($xmlStr, 'w:tbl')) {
            // 表格
            $class = new \cccms\phpreaddocxmath\logic\Extract\TableExtract($xml, $this->docxService);
            $xml = $class->handelOver($xml);
        }
        foreach (ExtractConfig::CONFIG as $class) {
            $class = new $class($xml, $this->docxService);
            $xml = $class->handel();
        }
        $xml = trim(strip_tags($xml));
        foreach (ExtractConfig::CONFIG as $class) {
            $class = new $class($xml, $this->docxService);
            $xml = $class->handelOver($xml);
        }
        if (str_contains($xmlStr, 'm:oMath')) {
            // 公式
            $xml = $math->handelOverDiy($xml);
        }
        return trim($xml);
    }

    /**
     * @param $xml
     * @return mixed
     */
    private function getHtmlStringCopy($xml)
    {
        $math = '';
        /**@var ExtractAbstruct $class */
        foreach (ExtractConfig::CONFIG as $class) {
            $class = new $class($xml, $this->docxService);
            if ($class instanceof MathExtract) {
                $math = $class;
            }
            if ($class instanceof ImgExtract) {
                $xml = $class->handel($this->img_handel_class);
            } else {
                $xml = $class->handel();
            }
        }
        $xml = trim(strip_tags($xml));
        /**@var ExtractAbstruct $class */
        foreach (ExtractConfig::CONFIG as $class) {
            $class = new $class($xml, $this->docxService);
            $xml   = $class->handelOver($xml);
        }
        if ($math instanceof MathExtract) {
            $xml = $math->handelOverDiy($xml);
        }
        return trim($xml);
    }

    /**
     * 自定义图片处理对象，可以不设置
     * @param string $class
     * @return $this
     */
    public function setImgHandelClass($class_name = '')
    {
        if (!$class_name) {
            return $this;
        }
        $class = new $class_name();
        if (!$class instanceof ImageDiyHandelInterface) {
            throw new \Exception($class_name . ' required ImageDiyHandelInterface');
        }
        $this->img_handel_class = $class_name;
        return $this;
    }
}
