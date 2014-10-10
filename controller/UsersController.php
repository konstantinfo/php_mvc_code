<?php
/**
 * @abstract This controller Created for User related action on this project
 * @Package Controller
 * @category Controller
 * @since 1.0.0 2-Feb-12
 * @copyright Copyright & Copy ; 2014
 *
 */
class UsersController extends AppController {

        public $name = 'Users';
        public $uses = array('User','EmailTemplate','Page','ZipCode','UserReferral', 'UserOrder', 'UserOrderHistory', 'UserOrdersTemp', 'GiftCardType');
        public $helpers = array('Html', 'Form', 'Session');
        public $components = array('Session','Upload','Email','Captcha','Auth' => array(
            'authenticate' => array(
                'Form' => array(
                    'scope'  => array('User.status' => '1')
                )
            )
        ));    
        
        public function beforeFilter() {
            parent::beforeFilter();
            $this->Auth->allow('admin_logout','captcha','login','registration','getStateShortName','facebook_login','forgot_password','logout','facebook_logged_in');
        }
        
        /**
	 * @abstract This function is define to login user from backend.
	 * @access Public
	 * @since 1.0.0 2-Feb-2014
	 */
        
        public function admin_login() {

            $this->layout = 'admin_login';

            if ($this->Session->read('Auth.User')) {

                if($this->Session->read('Auth.User.role') == 'admin')
                {
                    $this->redirect(array('admin' => true,'controller' => 'users','action' => 'dashboard'));
                }
                else
                {
                    $this->redirect('/', null, false);
                }
            }

            if ($this->request->is('post')) {
                if ($this->Auth->login()) {
                    if($this->Session->read('Auth.User.role') == 'admin')
                    {
                        $this->redirect(array('admin' => true,'controller' => 'users','action' => 'dashboard'));
                    }
                    else
                    {
                        $this->redirect('/', null, false);
                    }
                } 
                else {
                    $this->Session->setFlash(__('Invalid username or password.'));
                }
            }
        }
        
        /**
	 * @abstract This function is define to display all users from backend.
	 * @access Public
	 * @since 1.0.0 2-Feb-2014
	 */
        
        public function admin_index($status=null) {
            $this->layout = 'admin';
            $graphMonthLimit = 10;
			
            $condition = array();
            $separator = array();

            if (!empty($this->request->data)) {

                if (isset($this->request->data['User']['name']) && $this->request->data['User']['name'] != '') {
                    $user_name = trim($this->data['User']['name']);
                }            

                if (isset($this->request->data['User']['action'])) {
                    $idList = $this->data['User']['idList'];
                    if ($idList) {

                        if ($this->request->data['User']['action'] == "activate") {
                            $cnd = array("User.id IN ($idList) ");
                            $this->User->updateAll(array('User.status' => "'1'"), $cnd);                            
                        } 
                        elseif ($this->request->data['User']['action'] == "deactivate") {                           
                            $cnd = array("User.id IN ($idList) ");
                            $this->User->updateAll(array('User.status' => "'0'"), $cnd);
                        } 
                        elseif ($this->request->data['User']['action'] == "delete") {
                            $cnd = array("User.id IN ($idList) ");
                            $this->User->deleteAll($cnd);
                        }
                        
                        $this->Session->write('msg_type', 'confirm');
                        $this->Session->setFlash(__('Users '.$this->request->data['User']['action'].'d successfully'));
                    }                    
                }
            }
            elseif(!empty($this->request->params)){

                if(isset($this->request->params['named']['name']) && $this->params['named']['name']!=''){
                    $user_name = trim($this->request->params['named']['name']);
                    $this->request->data['User']['name'] = $user_name;
                }
                if(isset($this->request->params['named']['status']) && $this->params['named']['status']!=''){
                    $status= trim($this->request->params['named']['status']);
                }
            }

            if(isset($user_name) && $user_name != ''){
                $separator[] = 'name:'.$user_name;
                $condition['AND'] = array(
                                        'OR' => array(
                                                    'User.rep_id' => $user_name,
                                                    'User.first_name LIKE' => $user_name,
                                                    'User.last_name LIKE ' => '%'.$user_name.'%',
                                                    'User.email_address LIKE ' => '%'.$user_name.'%',
                                                    'User.address LIKE ' => '%'.$user_name.'%',
                                                    'User.city LIKE ' => '%'.$user_name.'%',
                                                    'User.state LIKE ' => '%'.$user_name.'%',
                                                    'User.zip_code' => $user_name
                                                )
                                        );
            }
			
            if((isset($this->data['User']['start_date']) && $this->data['User']['start_date'] != '') || (isset($this->data['User']['end_date']) && $this->data['User']['end_date'] != '')){
                $startArr = explode('/', $this->data['User']['start_date']);
                $endArr   = explode('/', $this->data['User']['end_date']);
                $condition['AND']['User.created BETWEEN ? AND ?'] = array($startArr[2].'-'.$startArr[0].'-'.$startArr[1].' 0:0:0', $endArr[2].'-'.$endArr[0].'-'.$endArr[1].' 0:0:0');
            }

            if(isset($status) && $status!=''){
                $separator[]='status:'.$status;

                if($status == 'active')
                $condition[] =" (User.status = 1) ";

                if($status == 'deactive')
                $condition[] =" (User.status = 0) ";
            }
            $condition[] =" (User.id != 1) ";

            $this->User->bindModel(array('hasOne' => array('UserReferral' => array('className' => 'UserReferral', 'foreignKey' => 'user_id', 'type'=>'INNER'))));
            $signup_rate_rep = $this->User->find('all', array('conditions' => $condition, 'fields' => 'FROM_UNIXTIME(UNIX_TIMESTAMP(User.`created`), "%M") AS month, FROM_UNIXTIME(UNIX_TIMESTAMP(User.`created`), "%Y") AS year, COUNT(User.`id`) AS total_signup', 'group' => 'FROM_UNIXTIME(UNIX_TIMESTAMP(User.`created`), "%M-%Y")', 'order' => 'FROM_UNIXTIME(UNIX_TIMESTAMP(User.`created`), "%M-%Y") ASC', 'limit' => $graphMonthLimit));
            $this->set('signup_rate_rep', $signup_rate_rep);
            
            $referred = $this->User->find('list', array('joins' => array(array('table' => 'user_referrals', 'alias' => 'user_referrals', 'type' => 'inner', 'foreignKey' => 'user_id', 'conditions' => array('user_referrals.user_id = User.id'))), 'fields' => 'User.id'));
            $condition['NOT'] = array('User.id' => $referred);

            $signup_rate = $this->User->find('all', array('conditions' => $condition, 'fields' => 'FROM_UNIXTIME(UNIX_TIMESTAMP(User.`created`), "%M") AS month, FROM_UNIXTIME(UNIX_TIMESTAMP(User.`created`), "%Y") AS year, COUNT(User.`id`) AS total_signup', 'group' => 'FROM_UNIXTIME(UNIX_TIMESTAMP(User.`created`), "%M-%Y")', 'order' => 'FROM_UNIXTIME(UNIX_TIMESTAMP(User.`created`), "%M-%Y") ASC', 'limit' => $graphMonthLimit));
            $this->set('signup_rate', $signup_rate);
            unset($condition['NOT']);
			
            $separator=implode("/",$separator);
            $this->set('separator',$separator);

            $this->paginate = array('conditions' => $condition, 'limit' => 10 );
            $this->set('users', $this->paginate('User'));
            $this->set('graphMonthLimit', $graphMonthLimit);
        }
        
        /**
	 * @abstract This function is define to display dashboard from backend.
	 * @access Public
	 * @since 1.0.0 2-Feb-2014
	 */
        
        public function admin_dashboard() {

            $this->layout = 'admin';
            $graphMonthLimit = 6;

            $purchase_rate = $this->UserOrder->find('all', array('fields' => 'FROM_UNIXTIME(`created`, "%M") AS month, FROM_UNIXTIME(`created`, "%Y") AS year, COUNT(`id`) AS total_purchase', 'group' => 'FROM_UNIXTIME(`created`, "%M-%Y")', 'order' => 'FROM_UNIXTIME(`created`, "%M-%Y") DESC', 'limit' => $graphMonthLimit));
            $this->set('purchase_rate', $purchase_rate);

            $signup_rate = $this->User->find('all', array('fields' => 'FROM_UNIXTIME(UNIX_TIMESTAMP(`created`), "%M") AS month, FROM_UNIXTIME(UNIX_TIMESTAMP(`created`), "%Y") AS year, COUNT(`id`) AS total_signup', 'group' => 'FROM_UNIXTIME(UNIX_TIMESTAMP(`created`), "%M-%Y")', 'order' => 'FROM_UNIXTIME(UNIX_TIMESTAMP(`created`), "%M-%Y") ASC', 'limit' => $graphMonthLimit));
            $this->set('signup_rate', $signup_rate);
            $this->set('graphMonthLimit', $graphMonthLimit);
        }       
        
        /**
	 * @abstract This function is define to add users from backend.
	 * @access Public
	 * @since 1.0.0 2-Feb-2014
	 */
		
        public function admin_add_user() {

            $this->layout = 'admin';
            $msgString = "";

            $this->set('states',$this->ZipCode->find('list',array('group'=>'full_state','fields'=>array('full_state','full_state'))));
            
            if ($this->request->is('post') || $this->request->is('put')) {

                if(empty($this->request->data["User"]["first_name"])){
                        $msgString .="First Name is required field.<br>";
                }
                elseif(trim($this->request->data["User"]["first_name"]) == "" ){
                        $msgString .= "Please enter valid first name.<br>";
                }
                if(empty($this->request->data["User"]["last_name"])){
                        $msgString .="Last Name is required field.<br>";
                }
                elseif(trim($this->request->data["User"]["last_name"]) == "" ){
                        $msgString .= "Please enter valid last name.<br>";
                }

                if(empty($this->request->data["User"]["email_address"])){
                        $msgString .="Email is required field.<br>";
                }
                elseif($this->User->checkEmail($this->request->data["User"]["email_address"]) == false)
                {
                        $msgString .="Please enter valid Email.<br>";
                }

                if($this->User->isRecordUniqueemail($this->request->data["User"]["email_address"]) == false)
                {
                        $msgString .="Email already exists.<br>";
                }

                if(empty($this->request->data["User"]["password"])){
                        $msgString .="Password is required field.<br>";
                }

                elseif(strlen($this->request->data["User"]["password"])<6){
                        $msgString .="Password must be at least 6 characters.<br>";
                }

                if(empty($this->request->data["User"]["confirm_password"])){
                        $msgString .="Confirm Password is required field.<br>";
                }

                $password = $this->request->data["User"]["password"];
                $conformpassword = $this->request->data["User"]["confirm_password"];

                if($password!=$conformpassword)
                {
                        $msgString.= "Password Mismatch.<br>";
                }

                if(isset($msgString) && $msgString!=''){
                    $this->Session->write('msg_type', 'error');
                    $this->Session->setFlash($msgString);
                }
                else
                {                    
                    
                    $destination = realpath('../../app/webroot/img/uploads/users/') . '/';

                    $file = $this->request->data['User']['image'];
                    
                    if(!empty($file['name']))
                    {
                        $this->Upload->upload($file, $destination, null, array('type' => 'resizecrop', 'size' => array('400', '300'), 'output' => 'jpg'));
                    
                        $errors = $this->Upload->errors;
                        
                        if (empty($errors)){
                                $this->request->data['User']['image'] = $this->Upload->result;                                               
                        } 
                        else {

                                if(is_array($errors)){ 
                                    $errors = implode("<br />",$errors);
                                }

                                $this->Session->write('msg_type', 'error');
                                $this->Session->setFlash($errors);
                                $this->redirect(array('admin' => true,'controller' => 'users','action' => 'add_user'));
                                exit();
                        }
                    }
                    else
                    {
                        unset($this->request->data['User']['image']);
                    }
                    
                    $this->request->data['User']['status']  = 1;
                    $this->request->data['User']['role']    = 'user';
                    $this->request->data['User']['username'] = $this->request->data['User']['email_address'];
                    $this->request->data['User']['rep_id'] = $this->User->generateUniqueRepID();
                    $this->request->data['User']['referral_url'] = $this->User->bitly_v3_shorten(Configure::read('Site.url').'/pages/reference/'.$this->request->data['User']['rep_id']);
                    
                    $password = $this->request->data['User']['password'];
                    
                    if ($this->User->save($this->request->data)) {
                        
                        // start mail code
                        
                        $email_template_detail = $this->EmailTemplate->find('first',array('conditions'=>array('EmailTemplate.email_type'=>'admin_registration')));
                        
                        $email_template = $email_template_detail['EmailTemplate']['message'];
                        $sender_email = $email_template_detail['EmailTemplate']['sender_email'];
                        $subject = $email_template_detail['EmailTemplate']['subject'];
                        $sender_name = $email_template_detail['EmailTemplate']['sender_name'];
                        
                        $email_template = str_replace('[site_title]',Configure::read('Site.title'),$email_template);
                        $email_template = str_replace('[username]',$this->request->data['User']['first_name'].' '.$this->request->data['User']['last_name'],$email_template);
                        $email_template = str_replace('[email]',$this->request->data['User']['email_address'],$email_template);
                        $email_template = str_replace('[password]',$password,$email_template);
                                                
                        $this->Email->to = $this->request->data['User']['email_address'];
                        $this->Email->subject = $subject;
                        $this->Email->replyTo = $sender_name."<".$sender_email.">";
                        $this->Email->from = $sender_name."<".$sender_email.">";                        
                        
                        $this->Email->sendAs = 'html';
                        $this->Email->template = 'default';
                                                
                        $this->set('message',$email_template);
                        
                        $this->Email->send();
                        
                        // end mail code
                        
                        $this->Session->write('msg_type', 'confirm');
                        $this->Session->setFlash(__('The user has been saved'));
                        $this->redirect(array('admin' => true,'controller' => 'users','action' => 'index'));
                    }
                }            
            } 
        }
        
        /**
	 * @abstract This function is define to edit users from backend.
	 * @access Public
	 * @since 1.0.0 2-Feb-2014
	 */
        
        public function admin_edit_user($id = null) {

            $this->layout = 'admin';
            $msgString = "";

            $this->set('states',$this->ZipCode->find('list',array('group'=>'full_state','fields'=>array('full_state','full_state'))));
            
            if (empty($id) && empty($this->request->data)) {
                $this->Session->write('msg_type', 'error');
                $this->Session->setFlash(__('Invalid User'));
                $this->redirect(array('admin' => true,'controller' => 'users','action' => 'index'));
            }

            if ($this->request->is('post') || $this->request->is('put')) {

                if(empty($this->request->data["User"]["first_name"])){
                        $msgString .="First Name is required field.<br>";
                }
                elseif(trim($this->request->data["User"]["first_name"]) == "" ){
                        $msgString .= "Please enter valid first name.<br>";
                }
                if(empty($this->request->data["User"]["last_name"])){
                        $msgString .="Last Name is required field.<br>";
                }
                elseif(trim($this->request->data["User"]["last_name"]) == "" ){
                        $msgString .= "Please enter valid last name.<br>";
                }
                
                if(empty($this->request->data["User"]["email_address"])){
                        $msgString .="Email is required field.<br>";
                }
                elseif($this->User->checkEmail($this->request->data["User"]["email_address"]) == false)
                {
                        $msgString .="Please enter valid Email.<br>";
                }
                
                if($this->request->data["User"]["email_address"] != $this->request->data["User"]["old_email_address"])
                {                
                    if($this->User->isRecordUniqueemail($this->request->data["User"]["email_address"]) == false)
                    {
                        $msgString .="Email already exists.<br>";
                    }
                }
                
                if(isset($msgString) && $msgString!=''){
                    $this->Session->write('msg_type', 'error');
                    $this->Session->setFlash($msgString);
                    $this->request->data['User']['image'] = $this->request->data['User']['old_image'];
                }
                else
                {
                    
                    $destination = realpath('../../app/webroot/img/uploads/users/') . '/';

                    $file = $this->request->data['User']['image'];
                                        
                    if(!empty($file['name']))
                    {
                        $this->Upload->upload($file, $destination, null, array('type' => 'resizecrop', 'size' => array('400', '300'), 'output' => 'jpg'));
                    
                        $errors = $this->Upload->errors;
                        
                        if (empty($errors)){
                                $this->request->data['User']['image'] = $this->Upload->result;                                               
                        } 
                        else {
                                if(is_array($errors)){ 
                                    $errors = implode("<br />",$errors);
                                }

                                $this->Session->write('msg_type', 'error');
                                $this->Session->setFlash($errors);
                                $this->redirect(array('admin' => true,'controller' => 'users','action' => 'edit_user',$this->request->data['User']['id']));
                                exit();
                        }
                    }
                    else
                    {
                        unset($this->request->data['User']['image']);
                    }
                    
                    $this->request->data['User']['username'] = $this->request->data['User']['email_address'];
                    
                    if ($this->User->save($this->request->data)) {
                        $this->Session->write('msg_type', 'confirm');
                        $this->Session->setFlash(__('The user has been updated'));
                        $this->redirect(array('admin' => true,'controller' => 'users','action' => 'index'));
                    }
                }            
            } 
            else {

                $this->User->id = $id;

                if (!$this->User->exists()) {
                    $this->Session->write('msg_type', 'error');
                    $this->Session->setFlash(__('Invalid User'));
                    $this->redirect(array('admin' => true,'controller' => 'users','action' => 'index'));
                }

                $this->request->data = $this->User->read(null, $id);
                unset($this->request->data['User']['password']);
                $this->request->data['User']['old_email_address'] = $this->request->data['User']['email_address'];
            }
        }

        /**
	 * @abstract This function is define to logout user from backend.
	 * @access Public
	 * @since 1.0.0 2-Feb-2014
	 */
        
        public function admin_logout() {
            $this->Session->setFlash('Logout Successfully.');  
            $this->Auth->logout();             
            $this->redirect(array('admin' => true,'controller' => 'users','action' => 'login'));
        }
        
        /**
	 * @abstract This function is define to login users from frontend.
	 * @access Public
	 * @since 1.0.0 2-Feb-2014
	 */
        
        public function login() {

            $this->layout = 'home';
            
            if ($this->Session->read('Auth.User')) {

                if($this->Session->read('Auth.User.role') == 'user')
                {
                    $this->redirect(array('controller' => 'users','action' => 'myaccount'));
                }
                elseif($this->Session->read('Auth.User.role') == 'admin')
                {
                    $this->redirect(array('admin' => true,'controller' => 'users','action' => 'dashboard'));
                }
            }
            
            $page_detail = $this->Page->find('first',array('conditions'=>array('Page.page_key'=>'login')));
            
            $title_for_layout = $page_detail['Page']['page_title'];
            $meta_keywords = $page_detail['Page']['keyword'];
            $meta_description = $page_detail['Page']['description'];
            $page_content = $page_detail['Page']['content'];
            
            $this->set(compact('meta_keywords', 'meta_description', 'page_content' ,'title_for_layout'));
                        
            if ($this->request->is('post')) {
                          
                if ($this->Auth->login()) {
				
                    // Check if user has registered with a referral URL :: relation must be mapped in database with status 0
                    $discount = $this->UserReferral->find('first', array('conditions' => array('user_id' => $this->Session->read('Auth.User.id'), 'status' => 0), 'fields' => 'limit'));
                    if(isset($discount['UserReferral'])){
                        $reference_discount = $this->requestAction(array('controller' => 'configurations', 'action' => 'configuration_value', 'reference_discount'));
                        $reference_purchase_limit = $this->requestAction(array('controller' => 'configurations', 'action' => 'configuration_value', 'reference_purchase_limit'));
                        if($this->Session->read('Cart.sub_total') > $reference_purchase_limit['Configuration']['value']){
                            $this->Session->write('Cart.discount', $reference_discount['Configuration']['value']);
                        }
                    }
                    else {
                        $this->Session->write('Cart.discount', 0);
                        $this->Session->delete('Referral');
                    }
					
                    $this->Session->write('discount_available', $this->Session->read('Auth.User.ring_rep_amount'));
                    $this->redirect(array('controller' => 'users','action' => 'myaccount'));
                } 
                else {                   
                    
                    $this->Session->write('msg_type', 'error');
                    $this->Session->setFlash(__('Invalid email or password.'));
                }
            }
        }
        
        /**
	 * @abstract This function is define to get password from frontend.
	 * @access Public
	 * @since 1.0.0 2-Feb-2014
	 */
        
        public function forgot_password(){            
            $this->layout = 'home';
            
            $msgString = "";
            
            $page_detail = $this->Page->find('first',array('conditions'=>array('Page.page_key'=>'forgot_password')));
            
            $title_for_layout = $page_detail['Page']['page_title'];
            $meta_keywords = $page_detail['Page']['keyword'];
            $meta_description = $page_detail['Page']['description'];
            $page_content = $page_detail['Page']['content'];
            
            $this->set(compact('meta_keywords', 'meta_description', 'page_content' ,'title_for_layout'));
        
            if ($this->request->is('post')) {
                
                $user_record = $this->User->find('first', array('conditions'=> array('User.email_address'=>$this->request->data["User"]["email_address"])));
                
                if(empty($this->request->data["User"]["email_address"])){
                        $msgString .="Email is required field.<br>";
                }
                elseif($this->User->checkEmail($this->request->data["User"]["email_address"]) == false)
                {
                        $msgString .="Please enter valid Email.<br>";
                }
                elseif(empty($user_record))
                {
                        $msgString .="No account found with that email address.<br>";
                }
                
                if(isset($msgString) && $msgString!=''){
                        $this->Session->write('msg_type', 'error');
                        $this->Session->setFlash($msgString);
                }
                else
                {
                    $this->User->create();
                    $this->request->data['User']['id'] = $user_record['User']['id'];
                    
                    $password = $this->User->generatePassword('8');                    
                    $this->request->data['User']['password'] = $password;
                    
                    $this->User->save($this->request->data);
                    
                    // start mail code
                        
                    $email_template_detail = $this->EmailTemplate->find('first',array('conditions'=>array('EmailTemplate.email_type'=>'forgot_password')));

                    $email_template = $email_template_detail['EmailTemplate']['message'];
                    $sender_email = $email_template_detail['EmailTemplate']['sender_email'];
                    $subject = $email_template_detail['EmailTemplate']['subject'];
                    $sender_name = $email_template_detail['EmailTemplate']['sender_name'];

                    $email_template = str_replace('[site_title]',Configure::read('Site.title'),$email_template);
                    $email_template = str_replace('[username]',$user_record['User']['first_name'].' '.$user_record['User']['last_name'],$email_template);
                    $email_template = str_replace('[email]',$this->request->data['User']['email_address'],$email_template);
                    $email_template = str_replace('[password]',$password,$email_template);

                    $this->Email->to = $this->request->data['User']['email_address'];
                    $this->Email->subject = $subject;
                    $this->Email->replyTo = $sender_name."<".$sender_email.">";
                    $this->Email->from = $sender_name."<".$sender_email.">";                        

                    $this->Email->sendAs = 'html';
                    $this->Email->template = 'default';

                    $this->set('message',$email_template);

                    $this->Email->send();

                    // end mail code  
                    
                    $this->Session->write('msg_type', 'confirm');
                    $this->Session->setFlash(__('An email has been sent to your emaill address with login credentials.'));
                    $this->redirect(array('controller' => 'users','action' => 'login'));
                }
            }
        }

        /**
	 * @abstract This function is define to register users from frontend.
	 * @access Public
	 * @since 1.0.0 2-Feb-2014
	 */
        
        public function registration() {

            $this->layout = 'home';
            $msgString = "";
			
            if ($this->Session->read('Auth.User')) {

                if ($this->Session->read('Auth.User.role') == 'user') {
                    $this->redirect(array('controller' => 'users', 'action' => 'myaccount'));
                } 
                elseif ($this->Session->read('Auth.User.role') == 'admin') {
                    $this->redirect(array('admin' => true, 'controller' => 'users', 'action' => 'dashboard'));
                }
            }

            $page_detail = $this->Page->find('first',array('conditions'=>array('Page.page_key'=>'registration')));
            
            $title_for_layout = $page_detail['Page']['page_title'];
            $meta_keywords = $page_detail['Page']['keyword'];
            $meta_description = $page_detail['Page']['description'];
            $page_content = $page_detail['Page']['content'];
            
            $this->set(compact('meta_keywords', 'meta_description', 'page_content' ,'title_for_layout'));
            
            $this->set('states',$this->ZipCode->find('list', array('group'=>'full_state','fields'=>array('full_state','full_state'))));
            
            if ($this->request->is('post') || $this->request->is('put')) {

                if(trim($this->request->data["User"]["first_name"]) == "" ||  trim($this->request->data["User"]["first_name"]) == 'First Name'){
                        $msgString .="First Name is required field.<br>";
                }
                
                if(trim($this->request->data["User"]["last_name"]) == "" ||  trim($this->request->data["User"]["last_name"]) == 'Last Name'){
                        $msgString .="Last Name is required field.<br>";
                }

                if(empty($this->request->data["User"]["email_address"])){
                        $msgString .="Email is required field.<br>";
                }
                elseif($this->User->checkEmail($this->request->data["User"]["email_address"]) == false)
                {
                        $msgString .="Please enter valid Email.<br>";
                }

                if($this->User->isRecordUniqueemail($this->request->data["User"]["email_address"]) == false)
                {
                        $msgString .="Email already exists.<br>";
                }

                if(empty($this->request->data["User"]["password"])){
                        $msgString .="Password is required field.<br>";
                }
                elseif(strlen($this->request->data["User"]["password"])<6){
                        $msgString .="Password must be at least 6 characters.<br>";
                }      
                
                if(empty($this->request->data["User"]["state"])){
                        $msgString .="State is required field.<br>";
                }

                if(isset($msgString) && $msgString!=''){
                    $this->Session->write('msg_type', 'error');
                    $this->Session->setFlash($msgString);
                }
                else
                {           
                    $this->request->data['User']['status']	= 1;
                    $this->request->data['User']['role']	= 'user';
                    $this->request->data['User']['username']	= $this->request->data['User']['email_address'];
                    $this->request->data['User']['rep_id']	= $this->User->generateUniqueRepID();
                    $this->request->data['User']['referral_url']= $this->User->bitly_v3_shorten(Configure::read('Site.url').'/pages/reference/'.$this->request->data['User']['rep_id']);
                    $password = $this->request->data['User']['password'];
					
                    if ($this->User->save($this->request->data)) {
                        
                        // Get rep user
                        $referrerData = $this->User->find('first', array('conditions' => array('rep_id' => $this->Session->read('Referral'), 'status' => 1), 'fields' => 'id'));
                        // Get current season of rinbg rep
                        $currentSeason = $this->requestAction(array('controller' => 'configurations', 'action' => 'configuration_value', 'current_rrc_season'));
                        $reference_discount = $this->requestAction(array('controller' => 'configurations', 'action' => 'configuration_value', 'reference_discount'));
                        $reference_purchase_limit = $this->requestAction(array('controller' => 'configurations', 'action' => 'configuration_value', 'reference_purchase_limit'));

                        // Check if referrence is already exists
                        $countIfExists = $this->UserReferral->find('count', array('conditions' => array('referrer_id' => $referrerData['User']['id'], 'user_id' => $this->User->id)));

                        // If referrer user exists, continue the process
                        if(isset($referrerData['User']) && !empty($referrerData['User']) && $countIfExists < 1){
                                $UserReferralData['UserReferral']['referrer_id'] = $referrerData['User']['id'];
                                $UserReferralData['UserReferral']['rep_id'] = $this->Session->read('Referral');
                                $UserReferralData['UserReferral']['user_id'] = $this->User->id;
                                $UserReferralData['UserReferral']['season'] = $currentSeason['Configuration']['value'];
                                $UserReferralData['UserReferral']['limit'] = $reference_purchase_limit['Configuration']['value'].'/'.$reference_discount['Configuration']['value'];
                                if($this->UserReferral->save($UserReferralData)){
                                        $this->Session->delete('Referral');
                                }
                        }
                        						
                        // start mail code
                        $email_template_detail = $this->EmailTemplate->find('first',array('conditions'=>array('EmailTemplate.email_type'=>'registration')));
                        
                        $email_template = $email_template_detail['EmailTemplate']['message'];
                        $sender_email = $email_template_detail['EmailTemplate']['sender_email'];
                        $subject = $email_template_detail['EmailTemplate']['subject'];
                        $sender_name = $email_template_detail['EmailTemplate']['sender_name'];
                        
                        $email_template = str_replace('[site_title]',Configure::read('Site.title'),$email_template);
                        $email_template = str_replace('[username]',$this->request->data['User']['first_name'].' '.$this->request->data['User']['last_name'],$email_template);
                        $email_template = str_replace('[email]',$this->request->data['User']['email_address'],$email_template);
                        $email_template = str_replace('[password]',$password,$email_template);
                                                
                        $this->Email->to = $this->request->data['User']['email_address'];
                        $this->Email->subject = $subject;
                        $this->Email->replyTo = $sender_name."<".$sender_email.">";
                        $this->Email->from = $sender_name."<".$sender_email.">";                        
                        
                        $this->Email->sendAs = 'html';
                        $this->Email->template = 'default';
                                                
                        $this->set('message',$email_template);
                        
                        $this->Email->send();
                        
                        // end mail code                        
                        
                        $this->Session->write('msg_type', 'confirm');
                        $this->Session->setFlash(__('Thank you, Your account has been created successfully.'));
                        $this->redirect(array('controller' => 'users','action' => 'facebook_logged_in',$this->User->id));
                    }
                }
            }
        }
        
        /**
	 * @abstract This function is define to display account page from frontend.
	 * @access Public
	 * @since 1.0.0 2-Feb-2014
	 */
        
        public function myaccount() {
            
            $this->layout = 'home';
            $this->User->id = $this->Session->read('Auth.User.id');
			
            $oldCart = $this->Session->read('Cart.temp_items');
            if(isset($oldCart) && !empty($oldCart)){
                foreach($oldCart as $ky => $val){
                    $exist = $this->UserOrdersTemp->find('first', array('conditions' => array('user_id' => $this->User->id, 'product_id' => $val['id'])));

                    if(isset($exist['UserOrdersTemp']) && !empty($exist['UserOrdersTemp'])){
                        $temp['UserOrdersTemp']['id'] = $exist['UserOrdersTemp']['id'];
                    }
                    $temp['UserOrdersTemp']['user_id'] = $this->User->id;
                    $temp['UserOrdersTemp']['product_id'] = $val['id'];
                    $temp['UserOrdersTemp']['product_attributes'] = $val['attributes'];

                    if($this->UserOrdersTemp->save($temp)){
                        unset($oldCart[$ky]);
                    }
                }
                    $this->Session->delete('Cart.temp_items');
            }
            			
            if (isset($this->data['User']['searchstr']) && !empty($this->data['User']['searchstr'])) {
                $conditions = array(
                    'User.id != ' => $this->User->id,
                    'User.total_reference > ' => 0,
                    'User.rrc_status' => 0,
                    'OR' => array(
                        'User.rep_id LIKE' => '%' . $this->data['User']['searchstr'] . '%',
                        'User.first_name LIKE' => '%' . $this->data['User']['searchstr'] . '%',
                        'User.last_name LIKE' => '%' . $this->data['User']['searchstr'] . '%'
                    )
                );
            } 
            else {
                $conditions = array('User.id != ' => $this->User->id, 'User.total_reference > ' => 0, 'User.rrc_status' => 0);
            }
            
            $otherReferalsData = $this->User->find('all', array( 'conditions' => $conditions, 'order' => 'User.total_reference DESC', 'limit' => 6 ));
            $this->set('otherReferalsData', $otherReferalsData);

            $this->set('your_ring', $this->UserOrderHistory->find('first', array('conditions' => array('user_id' => $this->User->id), 'fields' => 'product_attributes', 'order' => 'created DESC')));
            $this->set('saved_ring', $this->UserOrdersTemp->find('first', array('conditions' => array('user_id' => $this->User->id), 'fields' => 'product_attributes', 'order' => 'created DESC')));
            $this->set('referral_capoff_threshold', $this->requestAction(array('controller' => 'configurations', 'action' => 'configuration_value', 'referral_capoff_threshold')));
			
			
            $discountScheme = $this->UserReferral->find('first', array('conditions' => array('user_id' => $this->Session->read('Auth.User.id'), 'status' => 0), 'fields' => 'limit'));
            $reference_discount = $this->requestAction(array('controller' => 'configurations', 'action' => 'configuration_value', 'reference_discount'));
            $reference_purchase_limit = $this->requestAction(array('controller' => 'configurations', 'action' => 'configuration_value', 'reference_purchase_limit'));
            $this->Set('giftCardList', $this->GiftCardType->find('list', array('conditions' => array('status' => 1), 'fields' => 'id, gift_card_name')));
            
            $this->set('discountScheme', '');
            if(isset($discountScheme['UserReferral']) && $discountScheme['UserReferral']['limit'] != ''){                
                $this->set('discountScheme', $reference_purchase_limit['Configuration']['value'].'/'.$reference_discount['Configuration']['value']);
                $this->Session->write('reference_discount', $reference_discount['Configuration']['value']);
            }
			
            // Share content
            $this->set('fbShare', $this->requestAction(array('controller' => 'configurations', 'action' => 'configuration_value', 'fb_share')));
            $this->set('twitterShare', $this->requestAction(array('controller' => 'configurations', 'action' => 'configuration_value', 'twitter_share')));
			
            $page_detail = $this->Page->find('first', array('conditions' => array('Page.page_key' => 'myaccount')));
            $share_to_earn = $this->Page->find('first', array('conditions' => array('Page.page_key' => 'share_to_earn')));
			
            $title_for_layout = $page_detail['Page']['page_title'];
            $meta_keywords = $page_detail['Page']['keyword'];
            $meta_description = $page_detail['Page']['description'];
            $page_content = $page_detail['Page']['content'];
            $this->set(compact('meta_keywords', 'meta_description', 'page_content', 'title_for_layout','share_to_earn'));			
            
            $this->set('states', $this->ZipCode->find('list', array('group' => 'full_state', 'fields' => array('full_state', 'full_state'))));
            $this->request->data = $this->User->read();
            unset($this->request->data['User']['password']);
        }
	
        /**
	 * @abstract This function is define to logout users from frontend.
	 * @access Public
	 * @since 1.0.0 2-Feb-2014
	 */
        
        public function logout() {
            $this->Auth->logout();
            $this->Session->destroy();
            $this->redirect(array('controller' => 'users', 'action' => 'login'));
        }
                        
        /**
	 * @abstract This function is define to update users password info. from frontend.
	 * @access Public
	 * @since 1.0.0 2-Feb-2014
	 */
        
        public function update_password(){
            
            $msgString = '';
            
            if ($this->request->is('post') || $this->request->is('put')) {
                                
                if(empty($this->request->data["User"]["password"])){
                        $msgString .="Password is required field.<br>";
                }
                elseif(strlen($this->request->data["User"]["password"])<6){
                        $msgString .="Password must be at least 6 characters.<br>";
                }              
                
                if(!empty($msgString))
                {
                    echo $msgString;exit;
                }
                else
                {
                    $this->request->data['User']['id'] = $this->Session->read('Auth.User.id');
                    $this->User->save($this->request->data);
                    echo "success"; exit;
                }
            }
        }
        
        /**
	 * @abstract This function is define to update users billing info. from frontend.
	 * @access Public
	 * @since 1.0.0 2-Feb-2014
	 */
        
        public function update_billing(){
            $msgString = '';
            
            if ($this->request->is('post') || $this->request->is('put')) {
                                
                if(trim($this->request->data["User"]["first_name"]) == "" ||  trim($this->request->data["User"]["first_name"]) == 'First Name'){
                    $msgString .="First Name is required field.<br>";
                }
                
                if(trim($this->request->data["User"]["last_name"]) == "" ||  trim($this->request->data["User"]["last_name"]) == 'Last Name'){
                    $msgString .="Last Name is required field.<br>";
                }
              
                if(empty($this->request->data["User"]["state"])){
                    $msgString .="State is required field.<br>";
                }              
                
                if(!empty($msgString))
                {
                    echo $msgString;exit;
                }
                else
                {
                    $this->request->data['User']['id'] = $this->Session->read('Auth.User.id');
                    $this->User->save($this->request->data);
                    echo "success"; exit;
                }
            }
        }
        
        /**
	 * @abstract This function is define to update users shipping info. from frontend.
	 * @access Public
	 * @since 1.0.0 2-Feb-2014
	 */
        
        public function update_shipping(){
            $this->request->data['User']['id'] = $this->Session->read('Auth.User.id');
            $this->User->save($this->request->data);
            echo "success"; exit;
        }
}