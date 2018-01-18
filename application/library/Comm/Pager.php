<?php
/**
 * description: 分页类
 * author: lilong3@staff.sina.com.cn
 * createTime: 2016/5/19 16:23
 */
class Comm_Pager{
    public $firstRow; // 起始行数
    public $listRows; // 列表每页显示行数
    public $parameter; // 分页跳转时要带的参数
    public $totalRows; // 总行数
    public $totalPages; // 分页总页面数
    public $rollPage   = 6;// 分页栏每页显示的页数
	public $lastSuffix = true; // 最后一页是否显示总页数

    private $p       = 'page'; //分页参数名
    private $url     = ''; //当前链接URL
    private $nowPage = 1;

	// 分页显示定制
    private $config  = array(
        'header' => '共 <span id="pagerTotalNum">%TOTAL_ROW%</span> 条记录',
        'prev'   => '<',
        'next'   => '>',
        'first'  => '1...',
        'last'   => '...%TOTAL_PAGE%',
        'theme'  => '<p class="result-count">%HEADER%</p> <div class="page-count">%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%</div><div class="go-page">%GO_PAGE%</div>',
    );

    /**
     * 架构函数
     * @param array $totalRows  总的记录数
     * @param array $listRows  每页显示记录数
     * @param array $parameter  分页跳转的参数
     */
    public function __construct($totalRows, $listRows=20, $parameter = array()) {
        /* 基础设置 */
        $this->totalRows  = $totalRows; //设置总记录数
        $this->listRows   = $listRows;  //设置每页显示行数
        $this->parameter  = empty($parameter) ? $_GET : $parameter;
        $this->nowPage    = empty($_GET[$this->p]) ? 1 : intval($_GET[$this->p]);
        $this->nowPage    = $this->nowPage>0 ? $this->nowPage : 1;
        $this->firstRow   = $this->listRows * ($this->nowPage - 1);
    }

    /**
     * 定制分页链接设置
     * @param string $name  设置名称
     * @param string $value 设置值
     */
    public function setConfig($name,$value) {
        if(isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    /**
     * 生成链接URL
     * @param  integer $page 页码
     * @return string
     */
    private function url($page){
        return str_replace(urlencode('[PAGE]'), $page, $this->url);
    }

    /**
     * 组装分页链接
     * @return string
     */
    public function show() {
        if(0 == $this->totalRows) return '';

        /* 生成URL */
        $current_url = Comm_Tools::getIntactUrl();
        $this->parameter[$this->p] = '[PAGE]';
        $this->url = self::parseUrl($current_url, $this->parameter);
        /* 计算分页信息 */
        $this->totalPages = ceil($this->totalRows / $this->listRows); //总页数
        if(!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }

        /* 计算分页零时变量 */
        $now_cool_page      = $this->rollPage/2;
		$now_cool_page_ceil = ceil($now_cool_page);
		if($this->lastSuffix) {
            $this->config['last'] = $this->totalPages;
        }

        //上一页
        $up_row  = $this->nowPage - 1;
        $up_page = $up_row > 0 ? '<a aria-label="Previous"  href="' . $this->url($up_row) . '">' . $this->config['prev'] . '</a>' : '';

        //下一页
        $down_row  = $this->nowPage + 1;
        $down_page = ($down_row <= $this->totalPages) ? '<a aria-label="Next" href="' . $this->url($down_row) . '">' . $this->config['next'] . '</a>' : '';

        //第一页
        $the_first = '';
        if($this->totalPages > $this->rollPage && ($this->nowPage - $now_cool_page) >= 1){
            $the_first = '<a  href="' . $this->url(1) . '">' . $this->config['first'] . '</a>';
        }

        //最后一页
        $the_end = '';
        if($this->totalPages > $this->rollPage && ($this->nowPage + $now_cool_page) < $this->totalPages){
            $the_end = '<a  href="' . $this->url($this->totalPages) . '">' . $this->config['last'] . '</a>';
        }

        //数字连接
        $link_page = "";
        for($i = 1; $i <= $this->rollPage; $i++){
			if(($this->nowPage - $now_cool_page) <= 0 ){
				$page = $i;
			}elseif(($this->nowPage + $now_cool_page - 1) >= $this->totalPages){
				$page = $this->totalPages - $this->rollPage + $i;
			}else{
				$page = $this->nowPage - $now_cool_page_ceil + $i;
			}
            if($page > 0 && $page != $this->nowPage){

                if($page <= $this->totalPages){
                    $link_page .= '<a href="' . $this->url($page) . '">' . $page . '</a>';
                }else{
                    break;
                }
            }else{
                if($page > 0 && $this->totalPages != 1){
                    $link_page .= '<a class="bg-red" href="javascript:void(0)">' . $page . '</a>';
                }
            }
        }

        //JS跳转
        $extend = $go_page = '';
        if($this->totalPages>1) {
            $go_page = '<label>到第&nbsp;<input type="text"  style="text-align: center" id="retain_page_num">&nbsp;页</label><a href="javascript:goPage()">跳转</a>';
            if (!preg_match('/(.*?)(\?|&)page=([0-9]+)(.*)/i',$current_url)) {
                $sep = false===strpos($current_url,'?') ? '?' : '&';
                $current_url = $current_url.$sep.'page=1';
            }
            $extend = "<script type='application/javascript'>";
            $extend .= "var totalPages={$this->totalPages};current_url='{$current_url}';";
            $extend .= "function goPage(){var retain_page_num=document.getElementById('retain_page_num').value;if(!/^[0-9]*[1-9][0-9]*$/.test(retain_page_num)){alert('页数必须为正整数！');return false;} if(retain_page_num>totalPages){retain_page_num=totalPages} if(retain_page_num<1){retain_page_num=1} window.location.href=current_url.replace(/(.*?)(\?|&)page=([0-9]+)(.*)/ig, function(s,t1,t2,t3,t4){return  t1+t2+'page='+retain_page_num+t4;});}";
            $extend .= "</script>";
        }

        //替换分页内容
        $page_str = str_replace(
            array('%HEADER%', '%NOW_PAGE%', '%UP_PAGE%', '%DOWN_PAGE%', '%FIRST%', '%LINK_PAGE%', '%END%', '%TOTAL_ROW%', '%TOTAL_PAGE%', '%GO_PAGE%'),
            array($this->config['header'], $this->nowPage, $up_page, $down_page, $the_first, $link_page, $the_end, $this->totalRows, $this->totalPages, $go_page),
            $this->config['theme']);

        return $page_str.$extend;
    }

    /**
     * 处理URL
     * @param $url
     * @param $param
     * @return string
     */
    private function parseUrl($url, $param) {
        $info = parse_url($url);
        $urlArr = explode('?',$url);
        $ret = $urlArr[0];
        $paramArr = array();
        if(isset($info['query']) && $info['query']!='') {
            parse_str($info['query'], $paramArr);
        }
        $paramArr[$this->p] = $param[$this->p];
        $ret .= '?'.http_build_query($paramArr);
        //锚点
        if (isset($info['fragment'])) {
            $anchor =   $info['fragment'];
            $ret .= '#'.$anchor;
        }

        return $ret;
    }
}
