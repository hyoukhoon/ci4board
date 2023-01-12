<?php

namespace App\Controllers;
use App\Models\BoardModel;//사용할 모델을 반드시 써줘야한다.

class Board extends BaseController
{
    public function list()
    {
        $db = db_connect();
        $page    = $this->request->getVar('page') ?? 1;//현재 페이지, 없으면 1
        $perPage = 10;//한 페이지당 출력할 게시물 수 
        $startLimit = ($page-1)*$perPage;//쿼리의 limit 시작 부분
        $sql = "select b.*, if((now() - regdate)<=86400,1,0) as newid 
        ,(select count(*) from memo m where m.status=1 and m.bid=b.bid) as memocnt
        ,(select m.regdate from memo m where m.status=1 and m.bid=b.bid order by m.memoid desc limit 1) as memodate
        ,(select count(*) from file_table f where f.type='board' and f.status=1 and f.bid=b.bid) as filecnt
        from board b where 1=1";
        $order = " order by bid desc";
        $limit = " limit $startLimit, $perPage";
        $query = $sql.$order.$limit;
        $rs = $db->query($query);
        $rs2 = $db->query($sql);
        $total = $rs2->getNumRows();//전체 게시물수

        $data['list'] = $rs->getResult();
        $data['total'] = $total;
        $data['page'] = $page;
        $data['perPage'] = $perPage;

        $pager = service('pager');//페이저를 호출한다.
        $pager_links = $pager->makeLinks($page, $perPage, $total, 'default_full');
        $data['pager_links'] = $pager_links;//페이징을 구현될 부분을 리턴한다.

        return render('board_list', $data);//view에 리턴
    }

    public function write()
    {
        if(!isset($_SESSION['userid'])){
            echo "<script>alert('로그인하십시오.');location.href='/login'</script>";
            exit;
        }
        return render('board_write');  
    }

    public function save()
    {
        if(!isset($_SESSION['userid'])){
            echo "<script>alert('로그인하십시오.');location.href='/login'</script>";
            exit;
        }
        $db = db_connect();
        $bid=$this->request->getVar('bid');//bid값이 있으면 수정이고 아니면 등록이다.
        $subject=$this->request->getVar('subject');
        $content=$this->request->getVar('content');
        $file_table_id=$this->request->getVar('file_table_id');

        if($bid){
            $query = "select * from board where bid=".$bid;
            $rs = $db->query($query);
            if($_SESSION['userid']==$rs->getRow()->userid){
                $sql="update board set subject='".$subject."', content='".$content."' where bid=".$bid;
                $rs = $db->query($sql);
                if(isset($file_table_id)){//첨부한 이미지가 있으면
                    $fti=explode(',',$file_table_id);
                    foreach($fti as $fi){
                        if(isset($fi)){
                            $sql2="update file_table set bid=".$bid." where fid=".$fi;
                            $rs2 = $db->query($sql2);                
                        }
                    }
                }
                return $this->response->redirect(site_url('/boardView/'.$bid));
            }else{
                echo "<script>alert('본인이 작성한 글만 수정할 수 있습니다.');location.href='/login';</script>";
                exit;
            }
        }

        //$file = $this->request->getFile('upfile');//첨부한 파일의 정보를 가져온다.
        $files = $this->request->getFileMultiple("upfile"); //다중 업로드 파일 정보
        $filepath = array();
        foreach($files as $file){
            if($file->getName()){//파일 정보가 있으면 저장한다.
                $filename = $file->getName();//기존 파일명을 저장할때 필요하다. 여기서는 사용하지 않는다.
                //$filepath = WRITEPATH. 'uploads/' . $file->store(); 매뉴얼에 나와있는 파일 저장 방법이다.여기서는 안쓴다.
                $newName = $file->getRandomName();//서버에 저장할때 파일명을 바꿔준다.
                $filepath[] = $file->store('board/', $newName);//CI4의 store 함수를 이용해 저장한다. 저장한 파일의 경로와 파일명을 리턴, 배열로 저장한다.
            }
        }

        $sql="insert into board (userid,subject,content) values ('".$_SESSION['userid']."','".$subject."','".$content."')";
        $rs = $db->query($sql);
        $insertid=$db->insertID();
        foreach($filepath as $fp){//배열로 저장한 파일 저장 정보를 디비에 입력한다.
            if(isset($fp)){
                $sql2="INSERT INTO file_table
                        (bid, userid, filename, type)
                        VALUES('".$insertid."', '".$_SESSION['userid']."', '".$fp."', 'board')";
                $rs2 = $db->query($sql2);                
            }
        }

        if(isset($file_table_id)){//첨부한 이미지가 있으면
            $fti=explode(',',$file_table_id);
            foreach($fti as $fi){
                if(isset($fi)){
                    $sql2="update file_table set bid=".$insertid." where fid=".$fi;
                    $rs2 = $db->query($sql2);                
                }
            }
        }

        return $this->response->redirect(site_url('/boardView/'.$insertid));
    }

    public function view($bid = null)
    {
        $db = db_connect();
        $query = "select b.*,(select GROUP_CONCAT(filename) from file_table f where f.bid=b.bid and f.type='board') as fs from board b where b.bid=".$bid;
        $rs = $db->query($query);
        $data['view'] = $rs->getRow();

        //메모
        $query2="select *, m.userid, m.regdate, m.memoid from memo m
                left join file_table f on m.memoid=f.memoid and f.type='memo'
                where m.status=1 and m.bid=".$bid." order by ifnull(m.pid,m.memoid) desc, m.memoid asc";
        $rs2 = $db->query($query2);
        $data['memoArray'] = $rs2->getResult();
        return render('board_view', $data);   
    }

    public function modify($bid = null)
    {
        $db = db_connect();
        $query = "select * from board b where b.bid=".$bid;
        $rs = $db->query($query);
        if($_SESSION['userid']==$rs->getRow()->userid){
            $data['view'] = $rs->getRow();
            $query3 = "select * from file_table where type='board' and bid=".$bid;
            $rs3 = $db->query($query3);
            $data['fs']=$rs3->getResult();
            return render('board_write', $data);  
        }else{
            echo "<script>alert('본인이 작성한 글만 수정할 수 있습니다.');location.href='/login';</script>";
            exit;
        }
        
    }

    public function delete($bid = null)
    {

        $db = db_connect();
        $query = "select * from board where bid=".$bid;
        $rs = $db->query($query);
        if($_SESSION['userid']==$rs->getRow()->userid){
            $query3 = "select * from file_table where type='board' and bid=".$bid;
            $rs3 = $db->query($query3);
            $fs=$rs3->getResult();
            foreach($fs as $f3){
                unlink('uploads/'.$f3->filename);
            }
            $query4 = "delete from file_table where type='board' and bid=".$bid;
            $rs4 = $db->query($query4);
            $query2 = "delete from board where bid=".$bid;
            $rs2 = $db->query($query2);
            return $this->response->redirect(site_url('/board'));
        }else{
            echo "<script>alert('본인이 작성한 글만 삭제할 수 있습니다.');location.href='/login';</script>";
            exit;
        }
    }

    public function save_image()
    {
        $db = db_connect();
        
        $file = $this->request->getFile('savefile');
            if($file->getName()){
                $filename = $file->getName();
                //$filepath = WRITEPATH. 'uploads/' . $file->store();
                $newName = $file->getRandomName();
                $filepath = $file->store('board/', $newName);
            }

            if(isset($filepath)){
                $sql2="INSERT INTO file_table
                        (bid, userid, filename, type)
                        VALUES('', '".$_SESSION['userid']."', '".$filepath."', 'board')";
                $rs2 = $db->query($sql2);
                $insertid=$db->insertID();                
            }

        $retun_data = array("result"=>"success", "fid"=>$insertid, "savename"=>$filepath);
        return json_encode($retun_data);
    }

    public function file_delete()
    {
        $db = db_connect();
        $fid=$this->request->getVar('fid');
        $file_table_id=$this->request->getVar('file_table_id');
        $query = "select * from file_table where fid=".$fid;
        $rs = $db->query($query);
        if(unlink('uploads/'.$rs->getRow()->filename)){
            $query2= "delete from file_table where fid=".$fid;
            $rs2 = $db->query($query2);
            $file_table_id=str_replace(",".$fid,'',$file_table_id);
        }
        
        
        $retun_data = array("result"=>"ok", "file_table_id"=>$file_table_id);
        return json_encode($retun_data);
    }
}
