<?php 

if ($userPosition == $assessmentState) { ?>

    <?php if ($statementAmount == 0 && $completeAssessment == 0) : ?>
        <button 
            type="button" 
            class="btn btn-primary mr-2" 
            onclick="alert('There\'s no competency for this job title!')">
            <i class="fa fa-check"></i> Submit Form
        </button>

    <?php elseif ($statementAmount != $completeAssessment) : ?>
        <button 
            type="button" 
            class="btn btn-primary mr-2" 
            onclick="alert('Please complete the form before submit!')">
            <i class="fa fa-check"></i> Submit Form
        </button>
    <?php else : ?>
        <a 
            href="<?= base_url('submit_form/'.$form_code) ?>" 
            class="btn btn-primary mr-2" 
            onclick="return confirm('Dengan ini anda menyatakan bahwa penilaian yang dilakukan adalah benar. Formulir penilaian yang telah Anda submit tidak dapat diubah kembali. Anda Yakin?')">
            <i class="fa fa-check"></i> Submit Form
        </a>
    <?php endif; ?>

<?php } else { ?>

    <button type="button" class="btn btn-success pull-right" onclick="alert('Form has submitted!')">
        <i class="fa fa-check"></i> Form has submitted
    </button>
    
<?php } ?>