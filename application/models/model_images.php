<?php
class Model_images extends CI_Model {
        
        function getAllImages($login)
        {
		$user = $this->db->where('login',$login)->get('users')->result_array();
		if(!$user) return false;
		else return $user[0];
        }
	
	function getByArticleId($article_id, $active = -1, $type = false)
	{
		if($active != -1) $this->db->where('active', $active);
		if($type) $this->db->where('type', $type);
		$this->db->where('article_id', $article_id);
		$this->db->order_by('num', 'ASK');
		return $this->db->get('images')->result_array();
	}
	
	function getByShopId($article_id, $active = -1, $type = false)
	{
		if($active != -1) $this->db->where('active', $active);
        if($type) $this->db->where('type', $type);
		$this->db->where('shop_id', $article_id);
		$this->db->order_by('num', 'ASK');
		return $this->db->get('images')->result_array();
	}
	
	function getByProductId($article_id, $active = -1, $type = false)
	{
		if($active != -1) $this->db->where('active', $active);
        if($type) $this->db->where('type', $type);
		$this->db->where('product_id', $article_id);
		$this->db->order_by('num', 'ASK');
		return $this->db->get('images')->result_array();
	}

	function getByCategoryId($category_id, $active = -1, $type = false)
	{
		if($active != -1) $this->db->where('active', $active);
        if($type) $this->db->where('type', $type);
		$this->db->where('category_id', $category_id);
		$this->db->order_by('num', 'ASK');
		return $this->db->get('images')->result_array();
	}

	function getNewNumForArticle($article_id)
	{
		$this->db->where('article_id', $article_id);
		$this->db->order_by('num', 'DESC');
		$this->db->limit(1);
		$img = $this->db->get('images')->result_array();
		$num = 0;
		if($img)
		{
			$num = $img[0]['num'] + 1;
		}
		return $num;
	}
	

	
	function getById($id)
	{
		$this->db->where('id', $id);
		$ret = $this->db->get('images')->result_array();
		if($ret) return $ret[0];
		else return false;
	}
}