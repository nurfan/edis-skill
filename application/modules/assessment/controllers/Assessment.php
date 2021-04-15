<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Assessment extends CI_Controller {

	private $nik, $group, $level, $grade, $section, $department, $position, $position_grade;

	public function __construct()
	{
		parent::__construct();
		if (!$this->session->userdata('login_session')) {
			redirect(base_url('logout'));
		}

		$loginSession = $this->session->userdata('login_session');
        $this->nik            = $loginSession['nik'];
        $this->group          = $loginSession['group'];
        $this->level          = $loginSession['level'];
        $this->grade          = $loginSession['grade'];
        $this->section        = $loginSession['section'];
        $this->department     = $loginSession['department'];
        $this->position       = $loginSession['position'];
        $this->position_grade = $loginSession['position_grade'];

		$this->load->model('assessment_model','assessment');
	}

	public function index()
	{
		switch ($this->group) {
			// for admin and HR
			case 1:
			case 2:
				$getJobtitleList = $this->assessment->available_form();
				break;
			default:
                $getJobtitleList = $this->assessment->get_jobtitle_by_head($this->nik);
				
				break;
		}

        $data['state']          = $this->db->get_where('positions', ['grade >' => 3])->result();
        $data['position_grade'] = $this->position_grade;
        $data['position']       = $this->position;
        $data['department']     = $this->department;
        $data['section']        = $this->section;
        $data['jobtitleList']   = $getJobtitleList;
        $data['dept_list']      = $this->db->get('departements')->result();
        $data['page']           = 'assessment_v';
		$this->load->view('template/template', $data);
	}

    /**
     * Set up participant before generate the form
     * 
     * @return void
     */
    public function form_list() : void
    {
        // access verification, only for admin
        if ($this->position_grade != '99') {
            redirect('dashboard','refresh');
        }

        $this->session->unset_userdata('setup_session');

        $data['recaps']      = $this->assessment->recap_participant();

        //print_r($this->db->last_query()); die();   

        $data['departments'] = $this->db->get('departements')->result();
        $data['page']        = "assessment_set_participant";
        $this->load->view('template/template', $data);        
    }

    /**
     * Get section depend the department id
     * @param int $dept_id
     * @return void
     */
    public function get_sections(int $dept_id) : void
    {
        $sections = $this->db->get_where('sections',['dept_id' => $dept_id])->result();
        $list = "<option value='' disabled=''></option>";
        foreach($sections as $row) {
            $list .= "<option value='".$row->id."'>" ;
            $list .= $row->name;
            $list .= "</option>";
        }
        echo $list;        
    }
    
    /**
     * Save session before create form
     * 
     * @return void
     */
    public function setup_supervisor()
    {
        $array = array(
            'department' => $this->input->post('department'),
            'section'    => $this->input->post('section'),
            'spv'        => $this->input->post('supervisor')
        );
        
        $this->session->set_userdata( 'setup_session', $array );
        redirect(base_url('assessment/setup_participant'),'refresh');
    }

    /**
     * Setup participant depend on the data which save before
     * 
     * @return void
     */
    public function setup_participant() : void
    {
        $dept = $this->session->userdata('setup_session')['department'];
        $sect = $this->session->userdata('setup_session')['section'];
        $spv  = $this->session->userdata('setup_session')['spv'];

        $data['detail']    = ['dept' => $dept, 'sect' => $sect, 'spv' => $spv];

        $active_year       = get_active_year();
        $code              = $active_year.'-'.explode(' - ',$spv)[0];
        $data['employees'] = $this->assessment->employee_by_superior($dept, $sect);

        $data['page']      = "setup_participant_v"; 
        $this->load->view('template/template', $data);
    }

    /**
     * Matching spv and his subordinate
     * 
     * @return void
     */
    public function create_employee_relation() : void
    {
        $employee = $this->input->post('employes');
        $jobtitle = $this->input->post('jobtitle');
        $spv      = $this->input->post('spv');
        $number_of_subordinates = count($this->input->post('employes'));
        for ($i = 0; $i < $number_of_subordinates; $i++) {
            // is employe exist in employe relation?
            $is_employe_has_spv = $this->db->get_where('employee_relations', ['nik' => $employee[$i]])->num_rows();
            if ($is_employe_has_spv > 0) {
                $set   = ['nik' => $employee[$i], 'head' => $spv];
                $where = ['nik' => $employee[$i]];
                $this->db->update('employee_relations', $set, $where);
            } else {
                $set   = ['nik' => $employee[$i], 'head' => $spv];
                $this->db->insert('employee_relations', $set);
            }
            $this->_create_form($employee[$i], $spv, (int) $jobtitle[$employee[$i]]);
        }

        redirect(base_url('assessment/employe'));
    }

    /**
     * Create form after save employee relation
     * @param array $employee
     * @param string $spv_nik
     * @param array $jobtitle
     * @return void
     */
    private function _create_form(string $employee, string $spv_nik, int $jobtitle) : void
    {
        $active_year = get_active_year();
        $code = 'AF-'.$jobtitle.'-'.$active_year.'-'.$spv_nik;
        // load competency
        $dictionary = $this->assessment->get_competency($jobtitle);
        // check whether assessment form has generate or not
        $is_form_available = $this->assessment->assessment_form_employe($code, $employee);

        // create form if job title has competency
        if ($dictionary->num_rows() > 0) {
            // insert form assessment if those doesn't exist
            if ($is_form_available->num_rows() < 1) {
                $this->_generate_form_assessment2($employee, $jobtitle, $spv_nik);
            }
        }
        return;        
    }

    /**
     * List of employee who have competency form
     * 
     * @return void
     */
    public function employee_list() : void
    {
        $dept = $this->session->userdata('setup_session')['department'];
        $sect = $this->session->userdata('setup_session')['section'];
        $spv  = $this->session->userdata('setup_session')['spv'];
        $data['detail'] = ['dept' => $dept, 'sect' => $sect, 'spv' => $spv];
        $data['result'] = $this->assessment->employe_has_competency($spv, $dept, $sect);
        $data['page']   = "recap_list_v";
        $this->load->view('template/template', $data);
    }

    /**
     * Create form assessment content
     * @param array $employes
     * @param int $jobtitle
     * @return void
     */
    private function _generate_form_assessment2($employee, int $jobtitle, string $spv) : void
    {
        $activeYear          = get_active_year();
        $is_employe_has_form = $this->assessment->is_employe_has_form($jobtitle, $activeYear, $employee);
        $code                = 'AF-'.$jobtitle.'-'.$activeYear.'-'.$spv;

        if ($is_employe_has_form < 1) {

            $assessment_form = [
                'code'   => $code,
                'nik'    => $employee,
                'job_id' => $jobtitle,
            ];
            $this->db->insert('assessment_forms', $assessment_form);
        }

        $is_form_state_available = $this->db->get_where('assessment_form_state', ['code_form' => $code])->num_rows();
        if ($is_form_state_available < 1) {
            $state = $this->_get_workflow_state2("", $spv, $jobtitle);
            $assessment_state = [
                'code_form' => $code,
                'state'     => $state
            ];
            // insert to assesment state
            $this->db->insert('assessment_form_state', $assessment_state);
        }

        // insert assesment question
        $this->_insert_assessment_question2($code);
        return;
    }

    /**
     * See detail form
     * @param string $form_id
     * @return void
     */
    public function see_detail(string $form_id) : void
    {
        $sect = get_section_by_jobtitle(explode('-', $form_id)[1]);
        $dept = get_department_by_section($sect)->id;
        $data['detail'] = [
            'dept' => $dept, 
            'sect' => $sect, 
            'spv'  => explode('-', $form_id)[3].' - '.user_name(explode('-', $form_id)[3])
        ];
        $data['result'] = $this->assessment->see_detail_form($form_id);
        $data['page']   = "recap_list_v";
        $this->load->view('template/template', $data);
    }

    /**
     * Remove form
     * @param string $form_id
     * @return void
     */
    public function remove_form(string $form_id) : void
    {
        $form_detail = $this->db->query("SELECT * FROM assessment_forms WHERE code  = '$form_id'")->result();
        foreach ($form_detail as $forms) {
            $formID[] = $forms->id;
        }

        $this->db->where_in('form_id', $formID);
        $this->db->delete('assessment_form_questions');

        $this->db->delete('assessment_form_state', ['code_form' => $form_id]);

        $this->db->delete('assessment_forms', ['code' => $form_id]);

        $this->db->delete('assessment_validations', ['code' => $form_id]);

        $this->session->set_flashdata('success_remove_data', 'Data successfully removed!');
        redirect(base_url('assessment/form_list'));
    }

    public function form_lang(string $code, string $lang){
        if ($lang == 'ID' || $lang == 'JPN') {
            $this->session->set_userdata('language', $lang);
        }

        redirect(base_url('form/'.$code));
    }

    /**
     * Show all employes by their jobtitle
     * @param int $jobtitle
     * @return void
     */
    public function form(string $code) : void
    {
        $jobtitle = explode('-', $code)[1];

        $data['active_year'] = get_active_year();
        $data['sectionId']   = get_section_by_jobtitle($jobtitle);
        $data['department']  = get_department_by_section($data['sectionId'])->id;

        $get_employes = $this->db->query("SELECT em.nik, em.name, em.job_title_id FROM employes em 
                                        JOIN assessment_forms af ON em.nik = af.nik 
                                        WHERE af.code = '$code'
                                        ORDER BY af.total_poin, em.name ASC");

        $data['jobTitleName'] = $this->db->where('id', $jobtitle)->get('job_titles')->row();

        // load competency
        $data['dictionary'] = $this->assessment->get_competency($jobtitle);

        // Taruh pengecheckan kalo matrix belum ada
        if ($data['dictionary']->num_rows() < 1) {
            $this->session->set_flashdata('fail_save_data', 'Matriks kompetensi tidak ditemukan , mohon lengkapi terlebih dahulu matriks kompetensi untuk job posisi tersebut!');
            redirect('assessment','refresh');
        }

        // check whether assessment form has generate or not
        $isFormExist = $this->assessment->is_assessment_form_exist2($code);

        // check whether form has submited or not
        $data['isSubmited'] = $this->db->where('code', $code)
                                        ->get('assessment_validations')
                                        ->num_rows();

        if ($data['isSubmited'] > 0) {
            $data['submitStatus'] = $this->db->where('code', $code)->get('assessment_validations')->row()->is_valid;
        } else {
            $data['submitStatus'] = 0;
        }

        /** then check number of assessment per job title
         * and compare with number of complete assessment
         * to get the comparison which will be use
         * to check whether the form can be submit or not
         */
        // number of statement per job title
        $data['statementAmount'] = $isFormExist->num_rows() * $data['dictionary']->num_rows();

        // value for upload form
        $data['job_title'] = $jobtitle;
        $data['form_code'] = $code;

        $data['position_code']  = $this->db->query("SELECT pos.code FROM employes em 
                                                    JOIN positions pos ON  em.position_id = pos.id
                                                    WHERE em.nik = '$this->nik'")->row()->code;

        $data['assessment_state'] = $this
                                        ->db
                                        ->get_where('assessment_form_state', ['code_form' => $code])
                                        ->row()
                                        ->state;
        
        // number of filled assessment
        $data['completeAssessment'] = $this->assessment->complete_assessment2($code);
        $data['employes'] = $get_employes;
        // $data['page'] = 'assessment_form_v';
        $data['page'] = 'assessment_form_fill';
        $this->load->view('template/template', $data);
    }

	/**
	 * Show all employes by their jobtitle
	 * @param int $jobtitle
	 * @return void
	 */
	public function form_unuse(string $jobtitle) : void
	{
        $data['active_year'] = get_active_year();
        $data['sectionId']   = get_section_by_jobtitle($jobtitle);
        $data['department']  = get_department_by_section($data['sectionId'])->id;

		$get_employes = $this->assessment->get_partisipant($this->nik,$jobtitle);
		$data['jobTitleName'] = $this->db->where('id', $jobtitle)->get('job_titles')->row();

		// load competency
        $data['dictionary'] = $this->assessment->get_competency($jobtitle);

        // Taruh pengecheckan kalo matrix belum ada
        if ($data['dictionary']->num_rows() < 1) {
            $this->session->set_flashdata('fail_save_data', 'Matriks kompetensi tidak ditemukan , mohon lengkapi terlebih dahulu matriks kompetensi untuk job posisi tersebut!');
            redirect('assessment','refresh');
        }

		// check whether assessment form has generate or not
		$isFormExist = $this->assessment->is_assessment_form_exist($data['active_year'], $jobtitle);

		// generate form if job title has competency
		if ($data['dictionary']->num_rows() > 0) {
			// insert form assessment if those doesn't exist
			if ($isFormExist->num_rows() < 1) {
				$this->_generate_form_assessment($get_employes, $jobtitle);
			}

			// change value of $get_employe if job_titles has competency matrixes
			$get_employes = $this->assessment->competency_by_jobtitle($data['active_year'], $jobtitle);
		}

		// check whether form has submited or not
		$data['isSubmited'] = $this->db->like('code', 'AF-'.$jobtitle.'-'.$data['active_year'], 'BOTH')
										->get('assessment_validations')
										->num_rows();

		if ($data['isSubmited'] > 0) {
			$data['submitStatus'] = $this->db->where('code', 'AF-'.$jobtitle.'-'.$data['active_year'])->get('assessment_validations')->row()->is_valid;
		} else {
			$data['submitStatus'] = 0;
		}

		/** then check number of assessment per job title
         * and compare with number of complete assessment
         * to get the comparison which will be use
         * to check whether the form can be submit or not
         */
		// number of statement per job title
		$data['statementAmount'] = $isFormExist->num_rows() * $data['dictionary']->num_rows();

		// value for upload form
		$data['job_title'] = $jobtitle;
        $data['form_code'] = 'AF-'.$jobtitle.'-'.$data['active_year'];

        $data['position_code']  = $this->db->query("SELECT pos.code FROM employes em 
                                                    JOIN positions pos ON  em.position_id = pos.id
                                                    WHERE em.nik = '$this->nik'")->row()->code;

        $data['assessment_state'] = $this
                                        ->db
                                        ->get_where('assessment_form_state', ['code_form' => $data['form_code']])
                                        ->row()
                                        ->state;
        
		// number of filled assessment
		$data['completeAssessment'] = $this->assessment->complete_assessment($jobtitle);
		$data['employes'] = $get_employes;
		// $data['page'] = 'assessment_form_v';
        $data['page'] = 'assessment_form_fill';
		$this->load->view('template/template', $data);
	}

	/**
	 * Create form assessment content
	 * @param array $employes
	 * @param int $jobtitle
	 * @return void
	 */
	private function _generate_form_assessment(array $employes, int $jobtitle) : void
	{
		$activeYear = get_active_year();

		foreach ($employes as $employe) {
			$isEmployeHasForm = $this->assessment->is_employe_has_form(
									$employe->job_title_id,
									$activeYear,
									$employe->nik
								);
			if ($isEmployeHasForm < 1) {
                $state = $this->_get_workflow_state("",$jobtitle);

				// create an array of assessment form data to make insert batch
				$assessmentForm[] = [
                    'code'   => 'AF-'.$employe->job_title_id.'-'.$activeYear,
                    'nik'    => $employe->nik,
                    'job_id' => $employe->job_title_id,
				];

                $assessmentState = [
                    'code_form' => 'AF-'.$employe->job_title_id.'-'.$activeYear,
                    'state'     => $state
                ];
			}
		}
		$this->db->insert_batch('assessment_forms', $assessmentForm);

        // insert to assesment state
        $this->db->insert('assessment_form_state', $assessmentState);

		// insert assesment question
		$this->_insert_assessment_question($activeYear, $jobtitle);
		return;
	}

    /**
     * Set workflow state when generate form
     * 
     * @return void
     */
    private function _get_workflow_state(string $state="",int $job_title=0)
    {
        if ($state == "") {
            return $this->assessment->get_first_state($this->nik, $job_title);
        }else{
            return $this->assessment->get_continue_state($this->nik);
        }
    }

    /**
     * Set workflow state when generate form
     * 
     * @return void
     */
    private function _get_workflow_state2(string $state="", string $spv, int $job_title=0)
    {
        if ($state == "") {
            return $this->assessment->get_first_state($spv, $job_title);
        }else{
            return $this->assessment->get_continue_state($spv);
        }
    }

    /**
     * Insert assessment question
     * @param string $activeYear
     * @param int $jobtitle
     * @return null
     */
    private function _insert_assessment_question2(string $code)
    {
        $getForm = $this->assessment->is_assessment_form_exist2($code)->result();
        foreach ($getForm as $form) {
            // check is assessment question form has exist
            $isQuestionExist = $this->db->where('form_id', $form->id)->get('assessment_form_questions');

            // if question doesn't exist, create it
            if ($isQuestionExist->num_rows() < 1) {
                $questionCompetency = $this->assessment->create_assessment_question($form->job_id);

                foreach ($questionCompetency as $competency) {
                    $assessmentQuestion[] = [
                        'form_id'       => $form->id,
                        'skill_unit_id' => $competency->unit_id,
                        'weight'        => $competency->bobot
                    ];
                }
            }
        }
        $this->db->insert_batch('assessment_form_questions', $assessmentQuestion);
        return;
    }

	/**
	 * Insert assessment question
	 * @param string $activeYear
	 * @param int $jobtitle
	 * @return null
	 */
	private function _insert_assessment_question(string $activeYear, int $jobtitle)
	{
		$getForm = $this->assessment->is_assessment_form_exist($activeYear, $jobtitle)->result();
		foreach ($getForm as $form) {
			// check is assessment question form has exist
			$isQuestionExist = $this->db->where('form_id', $form->id)->get('assessment_form_questions');

			// if question doesn't exist, create it
			if ($isQuestionExist->num_rows() < 1) {
				$questionCompetency = $this->assessment->create_assessment_question($form->job_id);

				foreach ($questionCompetency as $competency) {
					$assessmentQuestion[] = [
                        'form_id'       => $form->id,
                        'skill_unit_id' => $competency->unit_id,
                        'weight'        =>$competency->bobot
					];
				}
			}
		}
		$this->db->insert_batch('assessment_form_questions', $assessmentQuestion);
		return;
	}
	
	/**
	 * Get competency for each dictionary
	 * @param string $nik
	 * @param int $jobtitle 
	 * @param int $skillId
	 * @return void
	 */
	public function get_competency(string $nik, int $jobtitle, int $skillId) : void
    {
        $activeYear = get_active_year();
        $data['dict'] = $skillId;
        $data['nik'] = $nik;
        $data['job'] = $jobtitle;

        $competency = $this->assessment->get_competency_for_assessment($nik, $jobtitle, $skillId, $activeYear);

        $data['dictionary'] = $this->db->where('id', $skillId)->get('skill_dictionaries')->row();

        //var_dump($data['dictionary']);die();

        // get employe name
        $data['employname'] = $this->db->where('nik', $nik)->get('employes')->row();
        $data['competency'] = $competency;

        if ($this->session->userdata('language') == "JPN") {
            $this->load->view('assessment_modal_jpn_v', $data);
        }else{
            $this->load->view('assessment_modal_v', $data);
        }
    }

    /**
     * Store assessment point to DB
     * 
     * @return void
     */
    public function insert_poin($code) : void
    {
        $id_form        = $this->input->post('idform');
        $inputamount    = count($this->input->post('nilai_mentah'));
        $limitEmptyPoin = $inputamount-1;

        // prevent if user fill with empty poin for all statement
        if (count(array_unique($this->input->post('nilai_mentah'))) == 1) {
        	$this->session->set_flashdata('fail_save_data', 'Gagal menyimpan data! Minimal mengisi satu pernyataan!');
			redirect(base_url('form/'.$code));
        }

        // prevent if poin that inputed > 1
        $checkEmptyArray = array_filter($this->input->post('nilai_mentah'), function ($val) {
        	return $val == "";
        });

        if (count($checkEmptyArray) < $limitEmptyPoin) {
        	$this->session->set_flashdata('fail_save_data', 'Gagal menyimpan data! Hanya boleh mengisi satu pernyataan');
			redirect(base_url('form/'.$code));
        }
        // prevent end

        for ($i=0; $i < $inputamount; $i++) {
        	// prevent poin that bigger than 5
        	if ($this->input->post('nilai_mentah')[$i] > 5) {
        		$this->session->set_flashdata('fail_save_data', 'Gagal menyimpan data! Nilai tidak boleh lebih dari 5');
				redirect(base_url('form/'.$code));
        	}
            // compulate data in array 2 dimension
            $poin[] = [$this->input->post('nilai_mentah')[$i],$this->input->post('skill_id')[$i]];
        }

        // get poin based on assessed competency
        $assessedCompetency = max($poin);

        // set to max poin for each competency that under filled competency
        $filledArrayIndex = array_search($assessedCompetency, $poin);
        for ($n = 0; $n < $filledArrayIndex; $n++) {
        	$poin[$n][0] = 5;
        }

        // prevent for freak input after edit
        $dictionaryId = $this->db->where('id', $assessedCompetency[1])->get('skill_units')->row()->id_dictionary;
        // set poin to null
        $this->db->query("UPDATE assessment_form_questions SET poin = NULL 
						WHERE form_id =  $id_form
						AND skill_unit_id IN (SELECT id FROM skill_units WHERE id_dictionary = $dictionaryId)");

        for ($j = 0; $j <= $filledArrayIndex; $j++) {
        	$data = ['poin' => $poin[$j][0]];
	        $this->db->where('form_id', $id_form);
	        $this->db->where('skill_unit_id', $poin[$j][1]);
	        $this->db->update('assessment_form_questions', $data);
        }
        // $data = ['poin' => $assessedCompetency[0]];
        // $this->db->where('form_id', $id_form);
        // $this->db->where('skill_unit_id', $assessedCompetency[1]);
        // $this->db->update('assessment_form_questions', $data);

        /**
         * if amount of statement for each job title is not equal
         * with amount of filled statement
         * so total_poin in assessment_forms will not filled 
         */
        $assessForm = $this->db->where('id', $this->input->post('idform'))->get('assessment_forms')->row();
        $jobTitle = $assessForm->job_id;

        /** amount of statement base on job title */
        $statementAmountbyJobtitle = $this->db
        									->where('job_id', $jobTitle)
    										->where('deleted_at')
    										->get('skill_matrix')
    										->num_rows();

        /** check amount of filled statement  */
        $filledFormAmount = $this->db
        							->where('form_id', $this->input->post('idform'))
        							->where('poin is NOT NULL', NULL, FALSE)
        							->get('assessment_form_questions');

        $filledStatementPerDictionary = $this->db
			        							->select('COUNT(distinct b.id_dictionary) AS totalFilled')
			        							->from('assessment_form_questions a')
			        							->join('skill_units b','a.skill_unit_id = b.id')
			        							->where('a.form_id',$id_form)
			        							->where('a.poin IS NOT NULL', NULL, FALSE)
			        							->get()->row()->totalFilled;


        /**
         * if amount of statement base on job title is equal with amount of filled statement
         * so update total_poin in assessment_forms
        */
        if ($statementAmountbyJobtitle == $filledStatementPerDictionary) {
            $const = 0;
            foreach ($filledFormAmount->result() as $val) {
                $const = $const + ($val->weight / 5) * $val->poin;
            }
            $totalPoin = $const;
            $grade = get_assessment_grade($totalPoin);
            $this->db->where('id', $this->input->post('idform'))
                    ->update('assessment_forms',
                        [
                            'total_poin' => $totalPoin, 
                            'poin_grade' => $grade,
                            'audit_by'   => $this->session->userdata('login_session')['nik']
                        ]);
        /**
         * but if total_poin in assessment_forms has filled cause intentionally submit
         * update it to NULL
         */
        } elseif ($statementAmountbyJobtitle > $filledStatementPerDictionary) {
            $isTotalPoinNull = $this->db->where('id', $this->input->post('idform'))->get('assessment_forms')->row();
            if (!is_null($isTotalPoinNull->total_poin)) {
            	$this->db
            			->where('id', $this->input->post('idform'))
            			->update('assessment_forms',['total_poin' => NULL]);
            }
        }

        redirect(base_url('form/'.$code));
    }

    /**
     * Handle submit form assessment
     * @param int $jobtitleId
     * @return void
     */
    public function submit_form(int $jobtitleId) : void
    {
    	// set validation flag
    	if ($this->group == 3 && $this->level == 1) {
    		$flag = 1; // for asistant manager
    	} elseif($this->group == 3 && $this->level == 2) {
    		$flag = 2; // for manager
    	} elseif ($this->group == 3 && $this->level == 3) {
    		$flag = 3; // for GM
    	} elseif ($this->group == 2 && $this->level == 2) {
            $flag = 2; // for manager in HR
        } elseif ($this->group == 2 && $this->level == 3) {
        	$flag = 3; // for manager in HR
        }
        // get active year of assessment
        $activeyear = get_active_year();

        // is validation exist ?
        $isValidationExist = $this->db->where('code', 'AF-'.$jobtitleId.'-'.$activeyear)->get('assessment_validations')->num_rows();

        if ($isValidationExist > 0) {
        	$this->db->where('code', 'AF-'.$jobtitleId.'-'.$activeyear)->update('assessment_validations', ['is_valid' => $flag]);
        } else {
        	$this->db->insert('assessment_validations', ['code' => 'AF-'.$jobtitleId.'-'.$activeyear, 'is_valid' => $flag]);	
        }

        redirect(base_url('form/'.$jobtitleId));
    }

    public function submit_form_2(string $code) : void
    {
        $jobtitleId           = explode('-', $code)[1];
        $code_form            = $code;
        $get_assessment_state = $this->db->get_where('assessment_form_state',  ['code_form' => $code_form])->row();
        $workflow                = $this->_get_workflow_state2($get_assessment_state->state, $this->nik, $jobtitleId);

        if ($this->grade >= 8 ) {
            $state = 'PA';
        } else {
    	    $state = $workflow->code;
    	}

        $this->db->where('code_form', $code_form);
        $this->db->update('assessment_form_state', ['state' => $state]);
        redirect(base_url('form/'.$code_form));
    }


    /**
     * Export assessment for to excel
     * @param int $jobtitleId
     * @return void
     */
    public function export_assessment_to_excel(string $code)
    {
        $this->load->library('excel');
        $jobtitleId = explode('-', $code)[1];
        // active assessment year
        $data['activeyear']     = get_active_year();

        // job titlle id
        $data['jobtitle'] = $jobtitleId;
        
        // get job title name
        $data['jobtitlename']   = $this->db->where('id', $jobtitleId)->get('job_titles')->row();
        
        // load competency base on job title
        $data['dictionary']     = $this->assessment->get_assessment_matrix($jobtitleId);
        
        $data['numberofcolumn'] = count($data['dictionary']) + 3;
        
        // get emlployee base on job title
        $data['employee']       = $this->assessment->get_employee_assessment_form($code);
        
        $this->load->view('excel_assessment_form2', $data);
    }
    
	/**
	 * Look up each assessment poin from HR
	 * @param int $skillId
	 * @param string $nik
	 * @return void
	 */
	public function see_poin(int $skillId, string $nik, int $jobId) : void
    {
        // $data['dictionary']= $this->db->where('id', $skillId)->get('skill_dictionaries')->row();
        // $data['nik']       = $nik;
        // $data['poin']      = $this->assessment->get_poin($skillId, $nik);

        // $this->load->view('assessment_modal_view_poin', $data);

        $activeYear = get_active_year();
        $data['dict'] = $skillId;
        $data['nik'] = $nik;
        $data['job'] = $jobId;

        $competency = $this->assessment->get_competency_for_assessment($nik, $jobId, $skillId, $activeYear);

        $data['dictionary'] = $this->db->where('id', $skillId)->get('skill_dictionaries')->row();
        
        // get employe name
        $data['employname'] = $this->db->where('nik', $nik)->get('employes')->row();
        $data['competency'] = $competency;

        if ($this->session->userdata('language') == "JPN") {
            $this->load->view('assessment_modal_view_poin2_jpn', $data);
        }else{
            $this->load->view('assessment_modal_view_poin2', $data);
        }
        
    }

    /**
     * Load competency description in table header of assessment
     * @param int $id
     * @return void
     */
    public function competency_description(int $id) : void
    {
		$data['description'] = $this->db->where('id', $id)->get('skill_dictionaries')->row();
		$this->load->view('competency_description_modal', $data);    	
	}

    /**
     * Get detail form based on its code
     * @param string $code
     * @return void
     */
    public function get_form(string $code) : void
    {
        $explode_code = explode('-', $code);
        $jobtitle = get_jobtitle_name($explode_code[1]);

        $section_id = get_section_by_jobtitle($explode_code[1]);
        $section = get_section($section_id)->name;

        $departments = get_department( get_section($section_id)->dept_id );
        $spv = user_name($explode_code[3]);

        $response = [
            'code' => $code,
            'spv'  => $spv,
            'job'  => $jobtitle,
            'sect' => $section,
            'dept' => $departments,
        ];
        echo json_encode($response);
    }

    /**
     * Handle change state for assessment filling
     * 
     * @return void
     */
    public function change_state()
    {
        $state = $this->input->post('state');
        $code = $this->input->post('code');

        $this->db->where('code_form', $code);
        $this->db->update('assessment_form_state', ['state' => $state]);

        $this->session->set_flashdata('success_update_data', 'State successfully changed!');
        redirect(base_url('assessment'));
    }

    /**
     * Export all assessment form
     * 
     * @return void
     */
    public function export_all_form()
    {
        $department = $this->input->post('department');
        $this->load->library('excel');
        // active assessment year
        $data['activeyear'] = get_active_year();
        $data['department_name'] = get_department($department);
        $data['assessment'] = $this->assessment->group_assessment_form(get_active_year(), $department);
        // debug($data['assessment'],1);
        $this->load->view('excel_all_assessment_form', $data);
    }
	
	/**
     * Handle import assessment form
     */
    public function upload()
    {
		$this->load->model("dictionary_competancy", "dc");

		$job_title_id = $this->input->post('job_title_id');
		$form_code = $this->input->post('form_code');

		$fileName = time().$_FILES['userfile']['name'];
		$path_upload='./assets/excel/assessment/';
         
        $config['upload_path'] = $path_upload; //buat folder dengan nama assets di root folder
        $config['file_name'] = $fileName;
        $config['allowed_types'] = '*';
		$config['max_size'] = 10000;
		
		$this->load->library('upload', $config);
         
        if (!$this->upload->do_upload('userfile'))
		{
				$error = array('error' => $this->upload->display_errors());
				die($error);
		}
		else
		{
				$media = $this->upload->data();

				$this->load->library('PHPExcel');
				$tmpfname = $media['full_path'];
				
				
				
				try {
					$excelReader = PHPExcel_IOFactory::createReaderForFile($tmpfname);
					$excelObj = $excelReader->load($tmpfname);
					$worksheet = $excelObj->getSheet(0);
					$lastRow = $worksheet->getHighestRow();

					$id_form = $worksheet->getCell('A1')->getValue();
					$nik = $worksheet->getCell('A4')->getValue();
					
					$k1 = $worksheet->getCell('C3')->getValue();
					$id_k1 = $this->dc->get_dictionary_by_name($k1)->id;

					$k2 = $worksheet->getCell('C4')->getValue();
					$id_k2 = $this->dc->get_dictionary_by_name($k2)->id;
					
					$k3 = $worksheet->getCell('C5')->getValue();
					$id_k3 = $this->dc->get_dictionary_by_name($k3)->id;

					$k4 = $worksheet->getCell('C6')->getValue();
					$id_k4 = $this->dc->get_dictionary_by_name($k4)->id;

					$k5 = $worksheet->getCell('C7')->getValue();
					$id_k5 = $this->dc->get_dictionary_by_name($k5)->id;

					for ($row = 1; $row <= $lastRow; $row++) {
						$letter = 'A';
						for ($col = 1; $col <= 8; $col++ ){

							if ($row >= 4 && $letter == 'C') {
								$k = $row - 3;

								$poin = $worksheet->getCell($letter.$row)->getValue();

								$this->db->query("UPDATE assessment_form_questions SET poin = $poin 
													WHERE form_id =  $id_form
													AND skill_unit_id IN (SELECT id FROM skill_units WHERE id_dictionary = $dictionaryId)");
								
							}
							
							$letter++;
						}
				   	}
					
				} catch (\Throwable $th) {
					die($th);
				}
		}

		redirect(base_url('form/'.$job_title_id));
    }

    function ldap()
    {
        $data = [
            '23310566',
            '70042890',
            '22389854',
            '22661244',
            '22388583',
            '22391927',
            '22395246',
            '22391597',
            '22389409',
            '24142277',
            '70362761',
            '70470211',
            '23311628',
            '24104598',
            '24104618',
            '24142204',
            '24142205',
            '24142206',
            '24142207',
            '24142210',
            '24142211',
            '24142212',
            '24142223',
            '24142224',
            '24142227',
            '24142229',
            '24142231',
            '24142232',
            '24142234',
            '24142235',
            '24142236',
            '24142237',
            '24142243',
            '24142256',
            '24142260',
            '24142261',
            '24142262',
            '24142268',
            '24142269',
            '24142270',
            '24142273',
            '24142274',
            '24142280',
            '24142281',
            '24142287',
            '24142288',
            '24142291',
            '24142293',
            '24142294',
            '24142296',
            '24142297',
            '24142298',
            '24142301',
            '24142306',
            '24142317',
            '24142324',
            '24142326',
            '24142340',
            '24142348',
            '24142350',
            '24142357',
            '70148878',
            '70148881',
            '70353484',
            '70353485',
            '70353487',
            '70353494',
            '70470169',
            '70470178',
            '70470179',
            '70470181',
            '70470185',
            '70470204',
            '70470207',
            '70470209',
            '70470216',
            '70470219',
            '70470224',
            '70470225',
            '70470235',
            '70470236',
            '70470239',
            '70470243',
            '70470247',
            '70470267',
            '70470274',
            '70470276',
            '70470285',
            '70470295',
            '70470297',
            '70470299',
            '70470303',
            '70508230',
            '71499589',
            '22390125',
            '22273062',
            '24142217',
            '24142230'
        ];

        foreach ($data as $val) {
            $ldap[] = [
                'ldap_id' => $val
            ];
        }

        var_dump($ldap); exit();

        $this->db->insert_batch('users', $ldap);
    }

}

/* End of file Assessment.php */
/* Location: ./application/modules/assessment/controllers/Assessment.php */
