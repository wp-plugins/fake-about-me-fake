<?php





class Fakeaboutme_Widget extends WP_Widget{

	function __construct() {
		$widget_ops = array('classname' => 'widget_fakeaboutme', 'description' => __( "A about me form for your site") );
		parent::__construct('fakeaboutme', __('FakeAboutMe','fakeaboutme'), $widget_ops);
	}
 
 
 	function widget($args, $instance ){
		 		
		echo $args['before_widget'];
		echo $args['before_title'] . $instance['title'] . $args['after_title'];
 
		if($instance['identity'] > 0)
		{
			$data = get_post_meta($instance['identity'], 'fakeaboutme_metadata', true);
			
			$style = 'style="';
			if($instance['text'] != '') $style .= 'color:'.$instance['text'].'; ';
			if($instance['color'] != '') $style .= 'background-color:'.$instance['color'].'; ';
			$style .='"';
			?> 
            <ul class="fakeaboutme">
            <li>
                <h4 <?=$style?>><?=__('Bio','fakeaboutme')?></h4>
                <label><?=__('Name','fakeaboutme')?><span><?=$data['fakeaboutme_full_name']?></span></label>
                <?=!empty($data['fakeaboutme_gender']) ? '<label>'.__('Gender','fakeaboutme').'<span>'.$data['fakeaboutme_gender'].'</span></label>' : ''?>
                <?=!empty($data['fakeaboutme_birthday']) ? '<label>'.__('Birthday','fakeaboutme').'<span>'.$data['fakeaboutme_birthday'].'</span></label>' : ''?>
            </li>                    
            <li>
                <h4 <?=$style?>><?=__('Contact','fakeaboutme')?></h4>
                <label><?=__('Domain','fakeaboutme')?>
                    <span><a href="<?=fix_http($data['fakeaboutme_domain'])?>" target="_blank"><?=$data['fakeaboutme_domain']?></a></span></label>
                <label><?=__('Email','fakeaboutme')?>
                    <span><a href="mailto:<?=$data['fakeaboutme_email']?>" target="_blank"><?=$data['fakeaboutme_email']?></a></span></label>
                <label><?=__('Occupation','fakeaboutme')?><span><?=$data['fakeaboutme_occupation']?></span></label>
            </li>
            <li>
                <h4 <?=$style?>><?=__('Location','fakeaboutme')?></h4>
                <label><?=__('City','fakeaboutme')?><span><?=$data['fakeaboutme_city']?></span></label>
                <label><?=__('Address','fakeaboutme')?><span><?=$data['fakeaboutme_address']?></span></label>
                <?=!empty($data['fakeaboutme_state']) ? '<label>'.__('State','fakeaboutme').'<span>'.$data['fakeaboutme_state'].'</span></label>' : ''?>
                <?=!empty($data['fakeaboutme_zip']) ? '<label>'.__('Zip','fakeaboutme').'<span>'.$data['fakeaboutme_zip'].'</span></label>' : ''?>
            </li>             
            </ul>            
			<?
		}
		echo $args['after_widget']; 
	}
  
	
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$new_instance = wp_parse_args((array) $new_instance, array( 'title' => '','identity' => '', 'color' => '', 'text' => ''));
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['identity'] = strip_tags($new_instance['identity']);
		$instance['color'] = strip_tags($new_instance['color']);
		$instance['text'] = strip_tags($new_instance['text']);
		return $instance;
	}
 
	
	
	function form( $instance ) {
				
		$instance = wp_parse_args( (array) $instance, array( 'title' => 'Fake About Me', 'identity' => '0', 'color' => '#E5EBEC', 'text' => '#000' ) );		

		//Get Identities
		$args = array( 
			'post_type'       => 'fab_identities'
			,'posts_per_page' => 100
			,'orderby'        => 'post_title'
			,'post_status'    => 'publish'
		);
  
		$result = new WP_Query( $args );
		

		//need to add new identity
		if(empty($result->posts))
		{
			?>
            <p>
				<?=__( 'To use this widget go to (Identities/Add New) or click ' , 'fakeaboutme' )?>
                <a href="/wp-admin/post-new.php?post_type=fab_identities"><?=__('here')?></a>
            </p>
			<?
			return;
		}

		//Load Identities
		$identities = '';
		foreach($result->posts as $post){
			$sel = $post->ID == $instance['identity'] ? 'selected' : '';
			$identities .= '<option value="'.$post->ID.'" '.$sel.'>'.$post->post_title.'</option>';
		}
	?>
    
		<p><label for="<?=$this->get_field_id('title')?>"><?=__('Title','fakeaboutme')?>:
			<input id="<?=$this->get_field_id('title')?>" name="<?=$this->get_field_name('title')?>" type="text" class="widefat" value="<?php echo $instance['title']; ?>" />
        </label></p>
        
        <p><label for="<?=$this->get_field_id('color')?>"><?=__('Header Backgroud Color','fakeaboutme')?>:
			<input id="<?=$this->get_field_id('color')?>" name="<?=$this->get_field_name('color')?>" type="text" class="widefat" value="<?php echo $instance['color']; ?>" />
        </label></p>
        
         <p><label for="<?=$this->get_field_id('text')?>"><?=__('Header Text Color','fakeaboutme')?>:
			<input id="<?=$this->get_field_id('text')?>" name="<?=$this->get_field_name('text')?>" type="text" class="widefat" value="<?php echo $instance['text']; ?>" />
        </label></p>
        

	  	<p><label ><?=__('Identity','fakeaboutme')?>:
            <select id="<?=$this->get_field_id('identity')?>" name="<?=$this->get_field_name('identity')?>" class="widefat">
            	<option value="0"><?=__('no identity','fakeaboutme')?></value>
	            <?=$identities?>
            </select>
        </label></p>

        <p><a href="/wp-admin/post-new.php?post_type=fab_identities"><?=__('Add new Identity')?></a></p>
		<?php

	}

	   
  

}