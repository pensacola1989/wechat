<?php

class Wechat_model extends CI_Model {

	function __construct()
	{
		parent::__construct();
		$this->load->database();
	}


	/*
	*	$data 是一个实体
	*/
	public function storeWechatInfo($data)
	{
		$tag = !empty($data) && count($data) != 0;
		if($tag) {
			try {
				$this->db->insert('info',$data);
				echo 'ok';
				flush();
				ob_flush();
			} catch (Exception $e) {
				echo $e->errorMessage();
			}
		}	
	}

    /*
     * 通过找到某个类别下的所有url
     */
	public function getUrlsByCategory($category)
	{
		$this->db
            ->select('imgurl')
            ->from('info')
            ->where('wechatType',$category);
        $query = $this->db->get();
        //var_dump($query->result());
        return $query->result();
	}
}