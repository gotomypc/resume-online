<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Resume_model extends CI_Model
{

	function cat_info($index = false)
	{
		$user_id = $this->Common->user_id();
		if(!isset($user_id))
		{
			return -1;
		}
		
		$this->db->where('user_id', $user_id);
		$this->db->order_by('order_id', 'asc');
		$query = $this->db->get('cat');
		$cat_info = array();
		
		foreach($query->result() as $info)
		{			
			$cat_count = $this->type_count($info->id, $info->type_id);
			if(!$index)
				$cat_info[] = (object) array("count"=>$cat_count, "cat_id" =>$info->id, "type_id"=>$info->type_id, "title"=>$info->title, "order_id"=>$info->order_id);
			else
				$cat_info[$info->id] = (object) array("count"=>$cat_count, "type_id"=>$info->type_id, "title"=>$info->title, "order_id"=>$info->order_id);
		}
		return $cat_info;
	}
	
	function type_count($cat_id, $type_id, $req_data = FALSE)
	{
		$table_name = $this->Common->type_table($type_id);

		if($table_name==FALSE)
			return FALSE;

		$this->db->where('cat_id', $cat_id);		
		$query = $this->db->get($table_name);
		
		if ($req_data==FALSE)
			return $query->num_rows;
			
		return $query->result();
	}
	
	function save_title($title, $cat_id = FALSE)
    {
    	$data = array('title' => $title);
		$this->db->where('id', $cat_id);
		$this->db->update('cat', $data); 
    }
    
    function type_info($item_id = FALSE) {
		if ( $item_id !== FALSE )
			$this->db->where('id', $item_id);
		else if($this->session->userdata('resume_item') === FALSE)
			$this->db->order_by('order_id', 'desc');
		else
			$this->db->where('id', $this->session->userdata('resume_item'));
		
		$this->db->where('cat_id', $this->session->userdata('cat_id'));
		$table_name = $this->Common->type_table($this->session->userdata('type_id'));

		$query = $this->db->get($table_name,1);
		$item = reset($query -> result());

		//redundancy to assure that correct data is stored
		if($query->num_rows() == 0)
			$this->session->set_userdata('resume_item', false);
		else
			$this->session->set_userdata('resume_item', $item->id);

		return $item;
    }
    
    function delete($id, $type_id = FALSE)
    {
    	if(!$type_id)
    		$type_id = $this->session->userdata('type_id');
    	
    	return $this->Common->delete($id, $this->Common->type_table($type_id));
    }
    
    function deleteitem ($id, $order_id = false) {
    	$table_name = $this->Common->type_table($this->session->userdata('type_id'));
    	if($order_id === false)
    		$order_id = $this->Common->get_order_id($id, $table_name);
    	
    	if($this->Common->delete($id, $table_name, $this->session->userdata('cat_id'))) {
			$where = "cat_id = '" . $this->session->userdata('cat_id') . "'";
			$this->Common->fix_order_id($order_id, $table_name, $where);
		} else 
			return FALSE;
		return TRUE;
    }
    
    function add($object, $type_id = FALSE)
    {
    	if(!$type_id)
    		$type_id = $this->session->userdata('type_id');
    	
    	$object->cat_id = $this->session->userdata('cat_id');
    	$table_name = $this->Common->type_table($type_id);
    	$object->order_id = $this->Common->next_order_id($table_name, array("cat_id" => $object->cat_id) );
    	$this->db->insert($table_name, $object);
    	$this->session->set_userdata('resume_item', $this->db->insert_id());
    }
    
    function update($object, $id, $type_id)
    {
    	$table_name = $this->Common->type_table($type_id);
    	$this->db->where('id', $id);
    	$this->db->update($table_name, $object);
    }
    
    
    //functions for specific database calls
    function get_courses($uni_id = FALSE) {
    	if(!$uni_id)
    		$uni_id = $this->session->userdata("resume_item");
    	
    	$this->db->where('uni_id', $uni_id);
    	$this->db->order_by('order_id', 'desc');
    	$query = $this->db->get('courses');
    	return $query->result(); 
    }
    
    function add_course($title) {
    	$object->uni_id = $this->session->userdata('resume_item');
    	$object->course = $title;
    	$object->order_id = $this->Common->next_order_id("courses", array("uni_id" => $object->uni_id));
    	$this->db->insert("courses", $object);
    }
    
    function delete_course($id) {
    	$order_id = $this->Common->get_order_id($id, "courses");
    	$this->db->where("uni_id", $this->session->userdata("resume_item"));
    	$this->db->where("id", $id);
    	$this->db->delete("courses");
    	if($this->db->affected_rows() > 0 )	 {
    		$where = "uni_id='" . $this->session->userdata("resume_item") . "'";
    		$this->Common->fix_order_id($order_id, "courses", $where );
    		return TRUE;
    	}
		return FALSE;
    }
    function get_phrases($exp_id = FALSE) {
    	if(!$exp_id)
    		$exp_id = $this->session->userdata("resume_item");
    	
    	$this->db->where('exp_id', $exp_id);
    	$this->db->order_by('order_id', 'asc');
    	$query = $this->db->get('descript');
    	return $query->result(); 
    }
    
    function add_phrase($title) {
    	$object->exp_id = $this->session->userdata('resume_item');
    	$object->phrase = $title;
    	$object->order_id = $this->Common->next_order_id("descript", array("exp_id" => $object->exp_id));
    	$this->db->insert("descript", $object);
    }
    
    function delete_phrase($id) {
    	$order_id = $this->Common->get_order_id($id, "descript");
    	$this->db->where("exp_id", $this->session->userdata("resume_item"));
    	$this->db->where("id", $id);
    	$this->db->delete("descript");
    	if($this->db->affected_rows() > 0 )	 {
    		$where = "exp_id='" . $this->session->userdata("resume_item") . "'";
    		$this->Common->fix_order_id($order_id, "descript", $where );
    		return TRUE;
    	}
		return FALSE;
    }

    function get_skills($header_id = FALSE) {
    	if(!$header_id)
    		$header_id = $this->session->userdata("resume_item");
    	
    	$this->db->where('header_id', $header_id);
    	$this->db->order_by('order_id', 'asc');
    	$query = $this->db->get('skills');
    	return $query->result(); 
    }

    function add_skill($skill) {
		$this->db->where('name', $skill);
    	$query = $this->db->get('skill_list',1);
		$item = reset($query->result());
		if(isset($item->id))
			$object1->skill_id = $item->id;
		else
		{
			$this->db->where('name', $skill);
    		$query = $this->db->get('skill_queue',1);
			$item = reset($query->result());
			if(isset($item->id))
				$object1->skill_id = $item->id * -1;
			else
			{
				$object->name = $skill;
				$this->db->insert("skill_queue", $object);
				$object1->skill_id = $this->db->insert_id() * -1;
			}
		}
    	$object1->order_id = $this->Common->next_order_id("skills", array("header_id" => $object->header_id));
		$object1->header_id = $this->session->userdata('resume_item');
    	$this->db->insert("skills", $object1);
    }

    function delete_skill($id) {
    	$order_id = $this->Common->get_order_id($id, "skills");
    	$this->db->where("header_id", $this->session->userdata("resume_item"));
    	$this->db->where("id", $id);
    	$this->db->delete("skills");
    	if($this->db->affected_rows() > 0 )	 {
    		$where = "header_id='" . $this->session->userdata("resume_item") . "'";
    		$this->Common->fix_order_id($order_id, "skills", $where );
    		return TRUE;
    	}
		return FALSE;
    }

	function get_skill_name($skill_id){
		$this->db->where('id', abs($skill_id));

		
		if($skill_id > 0)
			$query = $this->db->get('skill_list');
		else
			$query = $this->db->get('skill_queue');
		$item = reset($query->result());

		return $item->name;
	}
	
	//write functions to display data in html
	function write_uni($item) {
		$data['id'] = $item->id;
		$data['name'] = $item -> name;
		$data['date'] = $item -> finish;
		$data['degree'] = $item -> degree;
		$data['description'] = $item -> description;
		$data['gpa'] = $item -> gpa;
		$data['courses'] = $this->get_courses($item->id);
		$this->load->view('table/uni_item', $data);
	}
	function write_skill_header($item) {
		$data['id'] = $item->id;
		$data['title'] = $item->name;
		$data['skills'] = $this->get_skills($item->id);
		$this->load->view('table/skill_header_item', $data);
	}
	function write_experience($item) {
		$data['id'] = $item->id;
		$data['company'] = $item->company;
		$data['position'] = $item->position;
		$data['finish'] = $item->finish;
		$data['start'] = $item->start;
		$data['location'] = $item->location;
		$data['phrases'] = $this->get_phrases($item->id);
		$this->load->view('table/experience_item', $data);
	}
	function write_additional($item) {
		$data['field'] = $item->field;
		$this->load->view('table/additional_item', $data);
	}
	function write_honors($item) {
		$data['name'] = $item->name;
		$data['award'] = $item -> description;
		$data['location'] = $item -> location;
		$data['date'] = $item -> acquired;
		$data['num_set'] = 0;
		if(strlen($data['award']) >0 )
			$data['num_set']++;
		if(strlen($data['name']) >0 )
			$data['num_set']++;
		if(strlen($data['location']) >0 )
			$data['num_set']++;
		
		$this->load->view('table/honors_item', $data);
	}
}


