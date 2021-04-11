<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Assessment_year extends CI_Model {

	public $variable;

	public function __construct()
	{
		parent::__construct();
		
	}

	public function getActiveYear()
	{
		return $this->db->select('*')->from('assessment_years')->where('is_active', 1)->get()->row();
	}
}