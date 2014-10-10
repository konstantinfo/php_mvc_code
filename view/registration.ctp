<?php
echo $this->Html->script('front/jquery.validate', array('inline' => false));
echo $this->Html->script('front/registration_validate', array('inline' => false));
?>

<section class="contaner">
    
    <section class="innerpage">

        <div class="breadcream"> 
            You are here:  
            <?php
            $this->Html->addCrumb('Sign Up',  '/users/registration',array('class'=>"active")) ;
            echo $this->Html->getCrumbs(' » ', 'Home');
            ?>
        </div>       
        
        <section class="aboutus">

            <?php   
            if($this->Session->read('Message'))
            {
            ?>
                <div class="alert-wrapper <?php echo $this->Session->read('msg_type');?> clearfix">
                        <div class="alert-text">
                            <?php echo $this->Session->flash(); ?>
                        </div>
                </div>
            <?php
            }
            ?>
            
            <aside class="width328">
                <div> <?php echo $this->Html->image('front/tellyourstory.png'); ?> </div>
                <?php echo $page_content; ?>
            </aside>
            
            <aside class="signup">                
                
                <?php echo $this->Form->create("User",array("id"=>"registration"));?>
                
                <dl>
                    <dd>                        
                        <?php echo $this->Form->text('User.email_address',array('value'=>'Enter Your Email','class'=>'inputsign','style'=>'width:232px;','onblur'=>'if(this.value==\'\') this.value=\'Enter Your Email\';','onfocus'=>'if(this.value==\'Enter Your Email\') this.value=\'\';'));?>
                        <span class="error_msg"></span>
                    </dd>
                    <dd>
                        <?php echo $this->Form->password('User.password',array('class'=>'inputsign','style'=>'width:232px;','placeholder'=>'Enter a Password'));?>
                        <span class="error_msg"></span>
                    </dd>
                    <dd>
                        <?php echo $this->Form->text('User.first_name',array('value'=>'First Name','class'=>'inputsign','style'=>'width:232px;','onblur'=>'if(this.value==\'\') this.value=\'First Name\';','onfocus'=>'if(this.value==\'First Name\') this.value=\'\';'));?> 
                    </dd>
                    <dd>                        
                        <?php echo $this->Form->text('User.last_name',array('value'=>'Last Name','class'=>'inputsign','style'=>'width:112px;','onblur'=>'if(this.value==\'\') this.value=\'Last Name\';','onfocus'=>'if(this.value==\'Last Name\') this.value=\'\';'));?> 
                        <?php echo $this->Form->select('User.state', $states, array('class'=>'inputsign','style'=>'width:105px; float:right; height:36px;','label'=>'','div'=>false,'empty'=>'State'))?>                        
                        <span class="error_msg"></span>
                    </dd>
                    <dd>
                        <?php echo $this->Form->submit('sign up', array('class'=>'loginbtn','label'=>'','div'=>false));?>
                    </dd>
                </dl>
                <p> 
                    <span> By clicking "Sign Up", you confirm that you accept the                         
                        <?php echo $this->Html->link("Terms & Conditions", array('controller'=>'pages','action'=>'terms_conditions'),array('target'=>'_blank')); ?>
                    </span> 
                </p>

                <h3>
                    Already a member? 
                    <?php echo $this->Html->link("Sign in now", array('controller'=>'users','action'=>'login')); ?>
                    .
                </h3>                
                
                <?php echo $this->Form->end(); ?>

            </aside>            
            
        </section>

    </section>
    
</section>