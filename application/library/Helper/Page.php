<?php

class Helper_Page {

    var $totalnum;
    var $pageRecNum;
    var $pagenum;
    var $url;
    var $pageData;

    /*
     * $pageRecNum  ÿҳ��Ŀ��
     * $pagenum ��ǰҳ����
     * $url ҳ��ǰ���url
     * $totalnum ����Ŀ����
     * $page �������
     */

    function __construct($pageRecNum, $pagenum, $url, $totalnum) {
        $this->pageRecNum = $pageRecNum;
        $this->pagenum = $pagenum;
        $this->url = $url;
        if (substr($this->url, 0, 1) == '?') {
            $this->url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . $this->url;
        }
        if ($totalnum === '') {
            $totalnum = 0;
        }
        $this->totalnum = $totalnum;
        $this->_setPageData();
    }

    private function _setPageData() {
        $page_count = 1;
        if ($this->totalnum) {
            if ($this->totalnum < $this->pageRecNum) {
                $page_count = 1;
            } else {
                if ($this->totalnum % $this->pageRecNum) {
                    $page_count = (int) ($this->totalnum / $this->pageRecNum) + 1;
                } else {
                    $page_count = $this->totalnum / $this->pageRecNum;
                }
            }
        }
        if ($this->pagenum <= 1) {
            $this->pagenum = 1;
            $this->pageData['firstpage'] = $_SERVER['REQUEST_URI'] . '#';
            $this->pageData['previouspage'] = $_SERVER['REQUEST_URI'] . '#';
        } else {
            $this->pageData['firstpage'] = $this->url . '1';
            $this->pageData['previouspage'] = $this->url . ($this->pagenum - 1);
        }
        if (($this->pagenum >= $page_count) || ($page_count == 0)) {
            $this->pagenum = $page_count;
            $this->pageData['nextpage'] = $_SERVER['REQUEST_URI'] . '#';
            $this->pageData['lastpage'] = $_SERVER['REQUEST_URI'] . '#';
        } else {
            $this->pageData['nextpage'] = $this->url . ($this->pagenum + 1);
            $this->pageData['lastpage'] = $this->url . $page_count;
        }
        $this->pageData['totalpage'] = $page_count;
        $this->pageData['pagenum'] = $this->pagenum;

        $this->pageData['from'] = ($this->pagenum - 1) * $this->pageRecNum + 1;
        if ($this->totalnum == 0) {
            $this->pageData['from'] = 0;
        }
        if ($this->pagenum * $this->pageRecNum > $this->totalnum) {
            $this->pageData['to'] = $this->totalnum;
        } else {
            $this->pageData['to'] = ($this->pagenum) * $this->pageRecNum;
        }

        $this->pageData['pageArr'] = $this->getPagination($this->pagenum, $page_count);
        $this->pageData['totalnum'] = $this->totalnum;
        $this->pageData['pageRecNum'] = $this->pageRecNum;
        $this->pageData['pageurl'] = $this->url;
    }

    function get_page_data() {
        return $this->pageData;
    }

    /*
      listnum  ��ʾҳ����, Ĭ��չʾ11ҳ
     */

    function getpagelist_v4($listnum = 7, $omimark = "...") {
        $pagelist = array();
        $begin = $last = array();

        $rim_num = floor($listnum / 2) + 1;

        if (($this->pagenum > $rim_num && $this->pageData['totalpage'] > $listnum) && ($this->pageData['totalpage'] - $this->pagenum > $rim_num)) {      // ��ͷ��...������ʱ
            $begin[] = array("num" => 1, "url" => $this->url . "1");
            $begin[] = array("num" => $omimark, "url" => "");
            $last[] = array("num" => $omimark, "url" => "");
            $last[] = array("num" => $this->pageData['totalpage'], "url" => $this->url . $this->pageData['totalpage']);

            $firstpage = $this->pagenum - $rim_num + 2;
            $endpage = $this->pagenum + $rim_num - 2;
        } elseif ($this->pagenum > $rim_num && $this->pageData['totalpage'] > $listnum) { // ֻ�п�ͷ��...ʱ
            $begin[] = array("num" => 1, "url" => $this->url . "1");
            $begin[] = array("num" => $omimark, "url" => "");

            $firstpage = $this->pageData['totalpage'] - $listnum + 2;
            $endpage = $this->pageData['totalpage'];
        } elseif ($this->pageData['totalpage'] - $this->pagenum > $rim_num && $this->pageData['totalpage'] > $listnum) { // ֻ�н�β��...ʱ
            $last[] = array("num" => $omimark, "url" => "");
            $last[] = array("num" => $this->pageData['totalpage'], "url" => $this->url . $this->pageData['totalpage']);

            $firstpage = 1;
            $endpage = $listnum - 1;
        } else { // û��...ʱ
            $firstpage = 1;
            $endpage = $this->pageData['totalpage'];
        }

        for ($i = $firstpage; $i <= $endpage; $i++) {
            $pagelist[$i]['num'] = $i;
            $pagelist[$i]['url'] = $this->url . $i;
        }

        $pagelist = array_merge($begin, $pagelist, $last);
        return $pagelist;
    }

    function getPagination($currentPage, $totalPage) {
        $result = array();
        if ($currentPage <= 3 && $totalPage <= 5) {
            for ($i = 1; $i <= $totalPage; $i++) {
                $result[] = $i;
            }
        } elseif ($currentPage <= 3 && $totalPage > 5) {
            for ($i = 1; $i <= 5; $i++) {
                $result[] = $i;
            }
        } else {
            if (($totalPage - $currentPage) >= 5) {
                for ($i = $currentPage - 2; $i <= ($currentPage + 2); $i++) {
                    $result[] = $i;
                }
            } else {
                for ($i = 1; $i < (5 - ($totalPage - $currentPage)); $i++) {
                    if ($currentPage == $i)
                        continue;
                    $result[] = $currentPage - $i;
                }
                $result[] = $currentPage;
                for ($i = 1; $i <= ($totalPage - $currentPage); $i++) {
                    $result[] = $currentPage + $i;
                }
                sort($result);
            }
        }
        return $result;
    }

}

?>