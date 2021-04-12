<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <?php if ($this->session->userdata('language') == 'JPN') { ?>
		<h4 class="modal-title" id="title-mod"><?= $description->name_jpn ?></h4>
	<?php }else{ ?>
		 <h4 class="modal-title" id="title-mod"><?= $description->name_id ?></h4>
	<?php }?>	
    
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-sm-12">
        	
        	<?php if ($this->session->userdata('language') == 'JPN') { ?>
        		<p> <?= $description->description_jpn; ?> </p>
        	<?php }else{ ?>
        		 <p> <?= $description->description; ?> </p>
        	<?php }?>		
        	
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
</div>