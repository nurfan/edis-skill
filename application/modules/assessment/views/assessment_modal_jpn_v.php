<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <?php if ($this->session->userdata('language') == "JPN") { ?>
        <h4 class="modal-title" id="title-mod"><?= $dictionary->name_jpn ?> | <?= $employname->name ?></h4>
        <h5><?= $dictionary->description_jpn ?></h5>
    <?php }else{ ?>
        <h4 class="modal-title" id="title-mod"><?= $dictionary->name_id ?> | <?= $employname->name ?></h4>
        <h5><?= $dictionary->description ?></h5>
    <?php } ?>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-sm-12">
             <div class="alert alert-info">
            <h4>Raw Score</h4>
            <p>
                1 = 初心者,&nbsp;&nbsp;&nbsp;  
                2 = ビギナー,&nbsp;&nbsp;&nbsp;  
                3 = 有能,&nbsp;&nbsp;&nbsp;  
                4 = 熟練している,&nbsp;&nbsp;&nbsp;  
                5 = 専門
            </p>
        </div>
        <div class="alert alert-warning">
            <p>使用可能な5つのステートメントから1つのステートメントのみを選択できます</p>
        </div>
    </div>
    <div class="table-wrap">
        <div class="table-responsive">
            <table class="table table-hover table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Level</th>
                        <th style="white-space:nowrap;">Description</th>
                        <th style="white-space:nowrap;">Raw Score</th>
                        <!-- <th style="white-space:nowrap;">Bobot</th> -->
                    </tr>
                </thead>
                <tbody>

                    <?php 
                        // to get only poin that inserted by assessor
                        foreach ($competency as $items) {
                            $collectPoin[] = $items->poin;
                            $collectId[]   = $items->unit_question;
                        } 

                        $checkEmptyArray = array_filter($collectPoin, function ($val) {
                            return $val == NULL;
                        });
                        
                        $numberofEmptyPoin = count($checkEmptyArray);
                        $lastPoinPosition  = count($collectPoin) - $numberofEmptyPoin;
                        $filledPoinId      = 0;

                        if ($lastPoinPosition > 0) {
                            $filledPoinId  = $collectId[$lastPoinPosition-1];
                        }
                    ?>

                    <?php $no = 1; ?>
                    <?php foreach ($competency as $items) : ?>
                    <tr>
                        <td><?= $no ?></td>
                        <td><?= $items->description_jpn ?></td>
                        <td>
                            <input 
                                type="text" 
                                class="input-nilai" 
                                id="inputid5" 
                                name="nilai_mentah[]" 
                                style="width:4em" 
                                maxlength="1" 
                                value="<?= $items->unit_question == $filledPoinId ? $items->poin : ''; ?>" 
                                onkeypress="return isNumber(event)"/>

                            <input type="hidden" name="idform" value="<?= $items->id ?>">
                            <input type="hidden" name="skill_id[]" value="<?= $items->unit_question ?>">
                            <input type="hidden" name="job" value="<?= $items->job_id ?>">
                            <input type="hidden" name="dicts" value="<?= $dict ?>">
                            <input style="width:4em" type="hidden" value="<?= $items->weight ?>" disabled>
                        </td>
                    </tr>
                    <?php $no++; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="submit" class="btn btn-success" id="submit">Save</button>
    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
</div>
<script>

    $('.input-nilai').on('keyup',function() {
        $('.input-nilai').prop('readonly',true);
        $(this).removeAttr('readonly');

        <?php for ($n=0; $n < count($competency); $n++) {
            echo "var nilai".$n." = document.getElementsByName('nilai_mentah[]')[".$n."].value;";
         } ?>
        
        // var nilai1 = document.getElementsByName('nilai_mentah[]')[1].value;
        // var nilai2 = document.getElementsByName('nilai_mentah[]')[2].value;
        // var nilai3 = document.getElementsByName('nilai_mentah[]')[3].value;
        // var nilai4 = document.getElementsByName('nilai_mentah[]')[4].value;

        if($(this).val() === "") {
            $('.input-nilai').removeAttr('readonly');
            $('#submit').hide('fast');
        } else {
            $(this).attr('readonly',true);
            $(this).removeAttr('readonly');
            $('#submit').show('fast');
        }

        if ($(this).val() == 0) {
            $('#submit').hide();
        } else {
            $('#submit').show('fast');
        }

        if($(this).val() > 5) {
            $(this).val('');
            alert('Nilai maksimal adalah 5!');
            $('.input-nilai').removeAttr('readonly');
            $('#submit').hide('fast');
        }

        if($(this).val() < 1 && $(this).val() !== '') {
            $(this).val('');
            alert('Nilai minimal adalah 1!');
            $('.input-nilai').removeAttr('readonly');
            $('#submit').hide('fast');
        }
    });

    function isNumber(evt) {
        evt = (evt) ? evt : window.event;
        var charCode = (evt.which) ? evt.which : evt.keyCode;
        if (charCode > 31 && (charCode < 48 || charCode > 57)) {
            return false;
        }
        return true;
    }
</script>