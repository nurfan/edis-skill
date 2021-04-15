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
    </div>
    <div class="table-wrap">
        <div class="table-responsive">
            <table class="table table-hover table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Level</th>
                        <th style="white-space:nowrap;">行動レベルと説明</th>
                        <th style="white-space:nowrap;">Raw Score</th>
                        <!-- <th style="white-space:nowrap;">Bobot</th> -->
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php foreach ($competency as $items) : ?>
                    <tr>
                        <td><?= $items->level ?></td>
                        <td><?= $items->description_jpn ?></td>
                        <td><input type="text" style="width:4em"  value="<?= $items->poin ?>" disabled=""></td>
                        <td><input style="width:4em" type="hidden" value="<?= $items->weight ?>" disabled=""></td>
                    </tr>
                    <?php $no++; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
</div>