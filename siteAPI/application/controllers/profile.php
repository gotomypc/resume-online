<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Profile extends CI_Controller {
	
	function __construct()
	{
		parent::__construct();
		$this->Common->is_logged_in();                 
		$this->load->model('Membership_model');	

	}
	
	function index()
	{
		$data['info'] = $this->Membership_model->get_info();
		$data['address'] = $this->Membership_model->get_address();
		$data['website'] = $this->Membership_model->get_website();		
		$data['phone'] = $this->Membership_model->get_phone();
		
		$data['context'] = 'profile_form';
		$this->load->view('template/main', $data);	
	}
	
	function update()
	{
		$data['address'] = $this->Membership_model->get_address();
		$data['website'] = $this->Membership_model->get_website();		
		$data['phone'] = $this->Membership_model->get_phone();
	
		if($this->Common->check_pass($this->input->post('old_pass'))) //check if old password was right
		{
		
			$this->load->library('form_validation');
			$data['info'] = $this->Membership_model->get_info();
		
			if($this->input->post('name') != $data['info']->name) 
			{
				$this->form_validation->set_rules('name', 'Name', 'required');
			}
			
			if($this->input->post('email') != $data['info']->email) 
			{
				$this->form_validation->set_rules('email', 'Email Address', 'trim|required|valid_email|is_unique[users.email]');
			}
			
			if(strlen($this->input->post('new_pass'))>0||strlen($this->input->post('new_pass_confirm'))>0)
			{
				$this->form_validation->set_rules('new_pass', 'Password', 'trim|required|min_length[4]|max_length[32]');
				$this->form_validation->set_rules('new_pass_confirm', 'Password Confirmation', 'trim|required|matches[new_pass]');
				$entered_new = true;
			}
			else
			{
				//if didn't enter new password, assign new_pass to what old_pass is (since it passed the check)
				//this is so that the update_user function doesn't change the new pass to ''
				//$this->input->post('new_pass')=$this->input->post('old_pass');
				$entered_new = false;
			}
			
			//if validation fails, else update user with information from post
			if($this->form_validation->run() == FALSE )
			{
				$data['context'] = 'profile_form';
				$this->load->view('template/main', $data);
			}
			else
			{
				if($entered_new)
					$this->Membership_model->update_user($this->input->post('name'),$this->input->post('email'),$this->input->post('new_pass'));
				else
					$this->Membership_model->update_user($this->input->post('name'),$this->input->post('email'));
				$data['success'] = "Your data was updated!";
			}
		}
		else
		{
			//enter code to tell user that old password was wrong
			$data['success'] = "Your old password was incorrect.";
		}
		
		redirect('/profile/');	
	
	}
	
	function delete()
	{
		$table_name = $this->uri->segment(3);
		$id = $this->uri->segment(4);
	
		$this->Common->delete($id,$table_name);
	
		redirect('/profile/');	
	}
	
	function modify()
	{

		$table_name = $this->uri->segment(3);
		$col_type = $this->uri->segment(4);
		if($this->input->post('action')=='Change')
		{
			$info = array($col_type=>$this->input->post($col_type), 'def'=>$this->input->post('def'));
			$this->Membership_model->update($info, $this->input->post('id'), $table_name);
		}
		else if($this->uri->segment(5) == "add")
		{
			$this->load->library('form_validation');

			$this->form_validation->set_rules($col_type, $col_type, 'required');
			
			//checks to see if field is empty
			if($this->form_validation->run() == FALSE )
			{
				$data['context'] = 'profile_form';
				$data['success'] = 'You suck I hate you. Also you must enter something in.';
				$this->load->view('template/main', $data);
			}
			else
			{
				$info = array($col_type=>$this->input->post($col_type), 'def'=>$this->input->post('def'), 'user_id'=>$this->Common->user_id());
				$this->Membership_model->add($info, $table_name);	
			}
		}	
		redirect('/profile/');
	}
	

}
