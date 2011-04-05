<?php
/**
 * Kalkun
 * An open source web based SMS Management
 *
 * @package		Kalkun
 * @author		Kalkun Dev Team
 * @license		http://kalkun.sourceforge.net/license.php
 * @link		http://kalkun.sourceforge.net
 */

// ------------------------------------------------------------------------

/**
 * Kalkun_model Class
 *
 * Handle all base database activity 
 *
 * @package		Kalkun
 * @subpackage	Base
 * @category	Models
 */
class Kalkun_model extends Model {

	/**
	 * Constructor
	 *
	 * @access	public
	 */	
	function Kalkun_model()
	{
		parent::Model();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Login
	 *
	 * Check login credential and set session
	 *
	 * @access	public   		 
	 */	
	function login()
	{
		$username = $this->input->post('username');
		$password = sha1($this->input->post('password'));
		$this->db->from('user');
		$this->db->where('username', $username);
		$this->db->where('password', $password);
		$query = $this->db->get();
		
		if($query->num_rows()=='1') {
			$this->session->set_userdata('loggedin', 'TRUE');
			$this->session->set_userdata('level', $query->row('level'));
			$this->session->set_userdata('id_user', $query->row('id_user'));
			$this->session->set_userdata('username', $query->row('username'));			
			redirect('kalkun');
		}
		else $this->session->set_flashdata('errorlogin', 'Your username or password are incorrect');
	}	

	// --------------------------------------------------------------------
	
	/**
	 * Get Folders
	 *
	 * List of custom folders
	 *
	 * @access	public   		 
	 */	
	function get_folders($option=NULL, $id_folder=NULL, $id_user=NULL)
	{
		$this->db->from('user_folders');
		
		switch($option)
		{
			case 'all':
				$this->db->where('id_folder >', '5');
			break;
			
			case 'exclude':
				$this->db->where('id_folder >', '5');
				$this->db->where('id_folder !=', $id_folder);
			break;
			
			case 'name':
				$this->db->where('id_folder', $id_folder);
			break;
		}
		
		if($id_folder!='5')
		{
			$this->db->where('id_user', $this->session->userdata('id_user'));
		}
		
		$this->db->order_by('name');
		return $this->db->get();	
	}

	// --------------------------------------------------------------------
	
	/**
	 * Add Folder
	 *
	 * Add custom folder
	 *
	 * @access	public   		 
	 */		
	function add_folder()
	{
		$data = array ('name' => $this->input->post('folder_name'),'id_user' => $this->input->post('id_user'));
		$this->db->insert('user_folders',$data);		
	}

	// --------------------------------------------------------------------
	
	/**
	 * Rename Folder
	 *
	 * Rename custom folder
	 *
	 * @access	public   		 
	 */			
	function rename_folder()
	{
		$this->db->set('name', $this->input->post('edit_folder_name'));
		$this->db->where('id_folder', $this->input->post('id_folder'));		
		$this->db->update('user_folders');
	}

	// --------------------------------------------------------------------
	
	/**
	 * Delete Folder
	 *
	 * Delete custom folder
	 *
	 * @access	public   		 
	 */		
	function delete_folder($id_folder=NULL)
	{
		$id_user = $this->session->userdata('id_user');
				
		// get inbox
		$this->db->select_as('inbox.ID', 'id_inbox');
		$this->db->from('inbox');
		$this->db->join('user_inbox', 'user_inbox.id_inbox=inbox.ID');
		$this->db->join('user_folders', 'user_folders.id_folder=inbox.id_folder');
		$this->db->where('user_folders.id_folder', $id_folder);
		$inbox = $this->db->get();
		
		// delete inbox and user_inbox
		foreach($inbox->result() as $tmp)
		{
			$this->db->where('ID', $tmp->id_inbox);
			$this->db->delete('inbox');

			$this->db->where('id_inbox', $tmp->id_inbox);
			$this->db->delete('user_inbox');		
		}
				
		// deprecated		
		// inbox
		/* $inbox = "DELETE i, ui
				FROM user_folders AS uf
				LEFT JOIN inbox AS i ON i.id_folder = uf.id_folder
				LEFT JOIN user_inbox AS ui ON ui.id_inbox = i.ID
				WHERE uf.id_folder = '".$id_folder."'";
		$this->db->query($inbox);*/

		// get sentitems
		$this->db->select_as('sentitems.ID', 'id_sentitems');
		$this->db->from('sentitems');
		$this->db->join('user_sentitems', 'user_sentitems.id_sentitems=sentitems.ID');
		$this->db->join('user_folders', 'user_folders.id_folder=sentitems.id_folder');
		$this->db->where('user_folders.id_folder', $id_folder);
		$sentitems = $this->db->get();

		// delete sentitems and user_sentitems
		foreach($sentitems->result() as $tmp)
		{	
			$this->db->where('ID', $tmp->id_sentitems);
			$this->db->delete('sentitems');

			$this->db->where('id_sentitems', $tmp->id_sentitems);
			$this->db->delete('user_sentitems');		
		}		
		
		// deprecated		
		// Sentitems
		/*$sentitems = "DELETE s, us
				FROM user_folders AS uf
				LEFT JOIN sentitems AS s ON s.id_folder = uf.id_folder
				LEFT JOIN user_sentitems AS us ON us.id_sentitems = s.ID
				WHERE uf.id_folder = '".$id_folder."'";
		$this->db->query($sentitems);*/	
		
		$this->db->delete('user_folders', array('id_folder' => $id_folder, 'id_user' => $id_user)); 
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Update Setting
	 *
	 * Update setting/user preferences
	 *
	 * @access	public   		 
	 */		
	function update_setting($option)
	{
		switch($option)
		{
			case 'general':
				$this->db->set('language', $this->input->post('language'));
				$this->db->set('paging', $this->input->post('paging'));
				$this->db->set('permanent_delete', $this->input->post('permanent_delete'));
				$this->db->set('delivery_report', $this->input->post('delivery_report'));
				$this->db->set('conversation_sort', $this->input->post('conversation_sort'));
				$this->db->where('id_user', $this->session->userdata('id_user'));
				$this->db->update('user_settings');
			break;
			
			case 'personal':
				$this->db->set('realname', $this->input->post('realname'));
				$this->db->set('username', $this->input->post('username'));
				$this->db->set('phone_number', $this->input->post('phone_number'));
				$this->db->where('id_user', $this->session->userdata('id_user'));
				$this->db->update('user');
				
				$sig_opt = $this->input->post('signatureoption');
				$this->db->set('signature', $sig_opt.';'.$this->input->post('signature'));
				$this->db->where('id_user', $this->session->userdata('id_user'));
				$this->db->update('user_settings');
			break;
			
			case 'appearance':
				$this->db->set('theme', $this->input->post('theme'));
				$this->db->set('bg_image', $this->input->post('bg_image_option').';background.jpg');
				$this->db->where('id_user', $this->session->userdata('id_user'));
				$this->db->update('user_settings');
			break;
			
			case 'password':
				$this->db->set('password', sha1($this->input->post('new_password')));
				$this->db->where('id_user', $this->session->userdata('id_user'));
				$this->db->update('user');				
			break;
		}		
	}

	// --------------------------------------------------------------------
	
	/**
	 * Get Setting
	 *
	 * Get setting/user preferences
	 *
	 * @access	public   		 
	 */		
	function get_setting()
	{
		$id_user = $this->session->userdata('id_user');
		$this->db->where('user.id_user', $id_user);
		$this->db->join('user', 'user.id_user = user_settings.id_user');
		return $this->db->get('user_settings');
	}

	// --------------------------------------------------------------------
	
	/**
	 * Check Setting
	 *
	 * Check for duplicate username or phone number
	 *
	 * @access	public   		 
	 */		
	function check_setting($param)
	{
		$this->db->from('user');
		switch($param['option'])
		{
			case 'username':
			$this->db->where('username', $param['username']);
			break;	
			
			case 'phone_number':
			$this->db->where('phone_number', $param['phone_number']);
			break;				
		}
		return $this->db->get();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Get Gammu Info
	 *
	 * Get gammu related information
	 *
	 * @access	public   		 
	 */	
	function get_gammu_info($option)
	{
		switch($option)
		{
			case 'gammu_version':
				$this->db->from('phones');
				$this->db->select('Client');
				$this->db->order_by('UpdatedInDB', 'DESC');
				$this->db->limit('1');
			break;
			
			case 'db_version':
				$this->db->from('gammu');
				$this->db->select('Version');			
			break;	
			
			case 'last_activity':
				$this->db->from('phones');
				$this->db->select('UpdatedInDB');	
				$this->db->order_by('UpdatedInDB', 'DESC');
				$this->db->limit('1');							
			break;
			
			case 'phone_imei':
				$this->db->from('phones');
				$this->db->select('IMEI');	
				$this->db->order_by('UpdatedInDB', 'DESC');
				$this->db->limit('1');		
			break;	
			
			case 'phone_signal':
				$this->db->from('phones');
				$this->db->select('Signal');	
				$this->db->order_by('UpdatedInDB', 'DESC');
				$this->db->limit('1');				
			break;	
			
			case 'phone_battery':
				$this->db->from('phones');
				$this->db->select('Battery');	
				$this->db->order_by('UpdatedInDB', 'DESC');
				$this->db->limit('1');				
			break;															
		}
		return $this->db->get();
	}
		
	// --------------------------------------------------------------------
	
	/**
	 * Get SMS Used
	 *
	 * Get SMS count used by user based on date
	 *
	 * @access	public   		 
	 */			
	function get_sms_used($option, $param)
	{
		switch($option)
		{
			case 'date':
				$this->db->select('sms_count');
				$this->db->from('sms_used');
				$this->db->where('sms_date', $param['sms_date']);
				$this->db->where('id_user', $param['user_id']);
				$res = $this->db->get()->row('sms_count');
				
				if(!$res) return 0;
				else return $res;
			break;	
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * Add SMS Used
	 *
	 * Add SMS counter used by user based on date
	 *
	 * @access	public   		 
	 */	
	function add_sms_used($user_id)
	{
		$date = date("Y-m-d");
		$count = $this->_check_sms_used($date, $user_id);
		if($count>0)
		{	
			$this->db->set('sms_count', $count+1);
			$this->db->where('sms_date', $date);
			$this->db->where('id_user', $user_id);
			$this->db->update('sms_used');
		}	
		else
		{
			$this->db->set('sms_count', '1');
			$this->db->set('sms_date', $date);
			$this->db->set('id_user', $user_id);
			$this->db->insert('sms_used'); 
		}
	}
	
	function _check_sms_used($date, $user_id)
	{
		$this->db->select("sms_count");
		$this->db->from('sms_used');
		$this->db->where('sms_date', $date);
		$this->db->where('id_user', $user_id);
		$res = $this->db->get()->row('sms_count');
		if(!$res) return 0;
		else return $res;
	}
}

/* End of file kalkun_model.php */
/* Location: ./application/models/kalkun_model.php */