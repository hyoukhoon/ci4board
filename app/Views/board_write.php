    <?php
        $file_table_id=0;
        if(isset($fs)){
            foreach($fs as $ff){
                $file_table_id.=",".$ff->fid;//첨부한 파일의 정보를 입력한다.
            }
        }
    ?>
    <form method="post" action="<?= site_url('/writeSave') ?>" enctype="multipart/form-data">
        <input type="hidden" name="bid" value="<?php echo isset($view->bid)?$view->bid:0;?>">
        <input type="hidden" name="file_table_id" id="file_table_id" value="<?php echo $file_table_id;?>"><!-- 첨부한 파일 아이디-->
        <div class="mb-3">
        <label for="exampleFormControlInput1" class="form-label">이름</label>
            <input type="text" name="username" class="form-control" id="exampleFormControlInput1" placeholder="이름을 입력하세요." value="<?php echo $_SESSION['username']?>">
        </div>
        <div class="mb-3">
        <label for="exampleFormControlInput1" class="form-label">제목</label>
            <input type="text" name="subject" class="form-control" id="exampleFormControlInput1" placeholder="제목을 입력하세요." value="<?php echo isset($view->subject)?$view->subject:'';?>">
        </div>
        <div class="mb-3">
        <label for="exampleFormControlTextarea1" class="form-label">내용</label>
        <textarea class="form-control" id="exampleFormControlTextarea1" name="content" rows="3"><?php echo isset($view->content)?$view->content:'';?></textarea>
        </div>
        <div class="mb-3">
                <input type="file" multiple name="upfile[]" id="upfile" class="form-control form-control-lg" aria-label="Large file input example">
        </div>
        <br />
        <div class="row row-cols-1 row-cols-md-6 g-4" id="imageArea"><!-- 첨부한 이미지 영역-->
        <?php
        if(isset($fs)){
          foreach($fs as $f){
          ?>
            <div class='col' id='f_<?php echo $f->fid;?>'><div class='card h-100'><img src='/uploads/<?php echo $f->filename;?>' class='card-img-top'><div class='card-body'><button type='button' class='btn btn-warning' onclick='file_del(<?php echo $f->fid;?>)'>삭제</button></div></div></div>
          <?php 
          }
        }?>
        </div>
        <br />
        <?php
        $btntitle=isset($view->bid)?"수정":"등록";
        ?>
            <button type="submit" class="btn btn-primary"><?php echo $btntitle;?></button>
    </form>

    <script>

$("#upfile").change(function(){
    var file_table_id = $("#file_table_id").val();
    farr = file_table_id.split(',');//첨부한 파일을 나눈다
    var files = $('#upfile').prop('files');
    var maxCnt = 0;
    for(var i=0; i < files.length; i++) {
        maxCnt = parseInt(farr.length)+i;//갯수를 더해준다.
        if(maxCnt>5){
            alert('첨부 이미지는 최대 5개까지 등록 가능합니다.');
            $('#upfile').val('');
            return false;
        }else{
            attachFile(files[i]);
        }
    }

    $('#upfile').val('');

});   

function attachFile(file) {
    var formData = new FormData();
    formData.append("savefile", file);
    $.ajax({
        url:'/save_image',
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        dataType : 'json' ,
        type: 'POST',
        success: function (return_data) {
                fid = $("#file_table_id").val() + "," + return_data.fid;
                $("#file_table_id").val(fid);
                var html = "<div class='col' id='f_"+return_data.fid+"'><div class='card h-100'><img src='/uploads/"+return_data.savename+"' class='card-img-top'><div class='card-body'><button type='button' class='btn btn-warning' onclick='file_del("+return_data.fid+")'>삭제</button></div></div></div>";
                $("#imageArea").append(html);
        }
    });

}

function file_del(fid){

if(!confirm('삭제하시겠습니까?')){
return false;
}
    
var data = {
    fid : fid,
    file_table_id : $("#file_table_id").val()
};
    $.ajax({
        async : false ,
        type : 'post' ,
        url : '/file_delete' ,
        data  : data ,
        dataType : 'json' ,
        error : function() {} ,
        success : function(return_data) {
                $("#file_table_id").val(return_data.file_table_id);
                $("#f_"+fid).hide();
        }
});

}

</script>    