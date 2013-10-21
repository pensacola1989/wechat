<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require APPPATH.'libraries/simple_html_dom.php';


/**
* 下载图片
* 功能：php完美实现下载远程图片保存到本地
* 参数：文件url,保存文件目录,保存文件名称，使用的下载方式
* 当保存文件名称为空时则使用远程文件原来的名称
*/
class Image
{
//	// 保存路径
//	private $save_dir;
	// 保存的方式
	private $type = 1;
	// 所属类别
	private $category;

	function __construct($type = 0, $category)
	{
	//	$this->save_dir = $save_dir;
		$this->type = $type;
		$this->category = $category;

	}

	/*
	* 根据url集合下载图片，
	* 并将新图片的url存入数据库
	*/
	public function downloadImgFromList($list)
	{
        echo count($list) . '<br/>';
        if(!empty($list))
        {
            foreach($list as $ls)
            {
                $this->downloadImgByUrl($this->category,$ls->imgurl,'');
                // update the database use the 'new image Path';
                
                echo $ls->imgurl;
//                echo '<h3 style="color:green;">' . $ls->wechatid . '</h3>';
                echo '<span style="color:#eebcff;">stored!</span><br/>';
                flush();
                ob_flush();
            }
        }
	}

	/*
	* 根据url和文件名下载并保存函数，
	* 返回一个状态信息数组
	*/
	private function downloadImgByUrl($save_dir,$url,$filename)
	{
		if(trim($url) == '')
			return array( 'filename' => '', 'save_path' => '', 'error' => 1);
		if(trim($save_dir) == '')
            $save_dir = './';
		if(trim($filename) == '')
		{
			$ext = strrchr($url, '.');
			if($ext != '.gif' && $ext != '.jpg')
				return array( 'filename' => '', 'save_path' => '', 'error' => 3);
			$filename = md5(time() . 'www' . rand(1000,2000)) . $ext;
		}
		if(0 !== strrpos($save_dir, '/'))
            $save_dir .= '/';
		if(!file_exists($save_dir) && !mkdir($save_dir,0777,true))
			return array( 'filename' => '', 'save_path' => '', 'error' => 5);

		if($this->type) {
			$ch = curl_init();
			$timeout = 5;
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
			$img = curl_exec($ch);
			curl_close($ch);
		} else {
			ob_start(); 
		    readfile($url);
		    $img = ob_get_contents(); 
		    ob_end_clean(); 
		}

        echo $save_dir . '--------' . $filename;
        flush();
        ob_flush();

		$fp2 = @fopen($save_dir . $filename,'a');
		fwrite($fp2, $img);
		fclose($fp2);
		unset($Img,$url);
		return array( 'filename' => $filename, 'save_path' => $save_dir, 'error' => 0);
	}
}

/**
* 读取某个分类下的所有信息
*/
class pageOfCateory 
{
	function __construct($category)
	{
		$this->category = $category;
		$this->setHtmlDom(1);
		$this->setTotalPageNum();
		//echo 'pageNum' . $this->totalPageNum;
	}
	// 总页数
	private $totalPageNum = '';
	// 获取的DOM对象
	private $htmlDOM = '';
	// url prefix
	private $urlPrefix = 'http://www.anyv.net/index.php/category-';
	// 类别对应的url上的id
	private $category = '';
	// 当前页
	private $pageNum = 0;
	// 某个类别中的某一页里的链接集合
	private $links = array();
	// 链接容器
	private $linkContainer = '.pic_article_home2';
	// 从页面中获取链接集合赋给links
	private function setHtmlDom($pageNum)
	{
		$urlParams = $pageNum == 1 ? '' : '-page-' . $pageNum;

		$url = $this->urlPrefix . $this->category . $urlParams;

		echo $url;

		$this->htmlDOM = file_get_html($url);
	}
	// 访问总页数的接口
	public function getTotalPageNum()
	{
		return $this->totalPageNum;
	}

	private function getLinksFromCurrentPage()
	{
		$li = $this->htmlDOM
					->find($this->linkContainer,2)
					->find('li');

		foreach ($li as $l) {
			array_push($this->links, $l->find('a',0)->href);
		}		
	}

	private function setTotalPageNum()
	{
		$childNodes = $this->htmlDOM
							->find('.pages',0)
							->find('div',0)
							->childNodes();
		if(count($childNodes)) 
		{
			foreach ($childNodes as $val) 
			{
				if($val != null && $val->class == '') 
				{
					$this->totalPageNum++;
				}
			}
		}
	}
	/*
	*	从已经抓取的链接中获取信息
	*/
	private function getInfomationFormLinks()
	{
		$model = array();
		$root_SLT = '#article';
		$extInfo_SLT = '#article_extinfo';
		if(count($this->links)) 
		{
			$data = array();

			foreach ($this->links as $links) {
				$html = file_get_html($links);
				if($html == null) 
				{
					continue;
				}
				$webChatInfo['name'] = $html->find($root_SLT,0)
											->find($extInfo_SLT,0)
											->first_child()
											->plaintext;

				$webChatInfo['content'] = $html->find($root_SLT,0)
												->find($extInfo_SLT,0)
												->plaintext;

				$id = $html->find($root_SLT,0)->find($extInfo_SLT,0)->plaintext;
				$id = preg_replace('/[\x{4e00}-\x{9fa5}]/iu', '',$id);
				$id = preg_replace('/[,:：]/', '', $id);
				$id = preg_replace("/<(.*?)>/","", $id); 
				$webChatInfo['wechatid'] = trim($id);

				$children = $html->find($root_SLT,0)->childNodes();
				// 获取微信账号的描述
				$des = '';
				foreach ($children as $val) {
					if(!$val->hasAttribute('id') && !$val->hasAttribute('class')) {
						$des .= $val->plaintext;
					}
				}

				$webChatInfo['des'] = $des;

				$webChatInfo['codeimg'] = $html->find($root_SLT,0)->find('img',0) 
										? $html->find($root_SLT,0)->find('img',0)->src 
										:  '';

				$webChatInfo['codethumb'] = str_replace('.jpg', '.thumb.jpg', $webChatInfo['codeimg']);
				$webChatInfo['date'] = time();
				$webChatInfo['type'] = $this->category;
				array_push($data, $webChatInfo);

				echo '<span style=color:red;>'.$webChatInfo['name'] . '</span>  push to array length <span style="color:lightblue;>' . count($data) . '</span><br/>';
				flush();
				ob_flush();
			}
			return $data;
		}
	}

	public function getData()
	{
		$currentPage = 1;
		$i = 0;
		while ($currentPage <= $this->totalPageNum) {
			$this->setHtmlDom($currentPage);
			$this->getLinksFromCurrentPage();			
			$currentPage++;
			$i++;
			echo 'forTime' . $i;
		}
		return $this->getInfomationFormLinks();
	}

}
class Home extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('Wechat_model','model');
	}

	private $categoryPageNum = 0;

	private $catePrefix = "http://www.anyv.net/index.php/category-";

	private $urlArr = array();

	private $categoryArray = array(
			'1',
			'2',
			'51',
			'19',
			'3',
			'20'
		);

	private function generateUrl() {
		foreach ($this->categoryArray as $id) {
			array_push($this->urlArr, $this->catePrefix . $id);
		}
		print_r($this->urlArr);
	}

	public function index()
	{
		header("Content-type: text/html; charset=utf-8");
		// $beginPage = 1;
		$handler = new pageOfCateory(1);
		// $totalPageNum = $handler->getTotalPageNum();
		// while ($beginPage <= $totalPageNum) {
			
		// }
		$data = $handler->getData();
		foreach ($data as $d) {
			$ret['wechatid'] = $d['wechatid'];
			$ret['wechatdes'] = $d['des'];
			//$ret['date'] = time();
			$ret['wechatname'] = $d['name'];
			$ret['imgurl'] = $d['codeimg'];
			$ret['wechatType'] = $d['type'];

			$this->storeToDB($ret);
		}
	}

	private function beginGetUrl() 
	{

	}

	private function storeToDB($data)
	{
		$this->model->storeWechatInfo($data);
	}

	public function dbTest() 
	{
        $imgUrls = $this->model->getUrlsByCategory(1);
        $img = new Image(1,'1');
        $img->downloadImgFromList($imgUrls);

		//$this->load->model('Wechat_model','model');
		// $d['wechatid'] = 'fuck';
		// $d['des'] = 'fuck you';
		// $d['codeimg'] = 'http://fuck.com';

		// $ret['wechatid'] = $d['wechatid'];
		// $ret['wechatdes'] = $d['des'];
		// $ret['date'] = time();
		// $ret['imgurl'] = $d['codeimg'];

		// $this->model->storeWechatInfo($ret);
	}

    public function test()
    {
        $val = "wwwww                 xxxxx";
        //$val = str_replace(array(' ','&nbsp;'),'',$val);
        $ret = preg_split('/ /',$val);
        $r = $ret[0] . ' ' . $ret[count($ret) - 1];
        echo $r;
    }

}